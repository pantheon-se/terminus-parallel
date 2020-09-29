# Mass Terminus Deployment

Due to some recent conversations around the completion time of running mass deployments (particularly for very large portfolios), we have recently dove into this problem to identify areas Terminus performance improvements, and research how to increase the number of concurrent deployments that can happen at once.

## Problem
Terminus is a process-driven, Symfony console application that implements cURL requests using PHP in the background to the Terminus API. There are some performance bottlenecks in the current implementation:

- Core (and contributed plugin) `mass` functions run commands serially, looping over a list of sites, and waiting for each site process to complete.
- Terminus API implements request timeouts when too many requests are submitted at once (but does provide automated retries).
- Terminus doesn't have a good dependency management system when creating new plugins.
  - Can't redeclare Symfony package that Terminus uses, or include packages that require newer versions of packages in Terminus core.

This currently makes it impossible to have a internal Terminus-based parallel processing solution, so we need to wrap Terminus commands with an external process manager.

## Parallel vs Interval Processing

When approaching this problem, the initial thought was to implement some kind of parallel processing technique. Parallel processing, by definition, is this:

> a mode of computer operation in which a process is split into parts that execute simultaneously on different processors attached to the same computer.

When we think about adding parallel processing to Terminus, this translates to running separate Terminus commands on independent processes so that multiple commands can be running at the same time, especially when related to deploying a large number of sites. Parallel processing though is generally referring to the number of available processors (CPU cores), but this is predominently meant for heavy computations where you need a whole core to do some heavy work. In this context, we're essentially sending some requests and waiting for responses, so we only need to manage light processes rather than utilizing a whole core per task.

It is important to note that both parallel and time interval based management runs jobs asynchronously, the difference is how the overall processes are managed.

### Interval processes

Create a number of concurrent processes, on a specific time interval, that run in the background. For example:

1. Create a list of tasks.
2. Cycle through the list, initiating each task with a delay (ex, `sleep 6` to create a new process every 6 seconds).
3. Utilize the ampersand at the end of the shell execution (`./deploy.sh site-name-1 &`) to create a background task
4. Depending on the process completion time, tasks will finish before creating too many new tasks.

![async](/Users/kyletaylor/Downloads/async.gif)

While this model implements a predictable minimum runtime, the major consideration is to not overload the system with too many background jobs which will increase the actual job time. For example, if we have 300 sites to deploy, we can estimate that with a 6 second delay, the minimum processing time will be 30 minutes (300 sites x 6 seconds = 30 min). But because there are a number of tasks embedded in the deployment sequence, a single task could take anywhere from 4 minutes to 10 minutes if competing for the same processor time. See the example code below for how to implement background jobs:

```
# Deployment sequence wrapper
function sequence() {
  local SITE=$1
  echo -e "Starting ${SITE}";

  # Check site upstream for updates, apply
  terminus site:upstream:clear-cache $1 -q
  terminus upstream:updates:apply $DEV -q

  # Deploy code to test and live
  terminus env:deploy $TEST --cc --updatedb -n -q
  terminus env:deploy $LIVE --cc --updatedb -n -q
}

# Loop through all sites, initiating deploy sequence.
for SITE in $SITES; do
	# Add ampersand (&) at the end to send the task to the background
  sequence $SITE &
  sleep 6
done
```



### Parallel processes

Allocating a set number of concurrent workers to run processes out of a job queue. For example:

1. Put a list of tasks in a queue.
2. Define a number of available workers.
3. Each worker will take a task out of the queue and initiate a process.
4. When the processing is a complete, the worker will pick up a new task until no more tasks are in the queue.

![parallel](/Users/kyletaylor/Downloads/parallel.gif)

The goal with either approach is to have multiple processes running concurrently in the background. The parallel processing method is simpler and the available workers can be dynamically expanded based on the environment but can overwhelm Terminus when initiatiting background calls, while the interval queue is static but requires more tuning based on how long jobs take.

## Solution

Using GitHub Actions, we're able to create a build process that will utilize a Pull Request workflow, and on successful merge to `master`, will initiate the build sequence that will fetch all sites using this custom upstream, then start a parallel process that will initiate a deploy sequence for each site.

![parallel-workflow](/Users/kyletaylor/Downloads/parallel-workflow.jpeg)

The magic function here is using GNU Parallel to manage the process handling. Essentially, we bundle the entire deployment sequence into a single script that takes a site ID as an argument. In this script, you can implement additional error handling (for example, as restoring a backup if we don't see an `exit 0`), but this simplistisc example does not.

```bash
# Get list of sites
SITES=$(terminus org:site:list ${ORG_UUID} --format list --upstream ${UPSTREAM_UUID} --field name | sort -V)

# Pass sites to deployment script, run in 50 parallel processes
echo $SITES | parallel --jobs 50 ./timeout-sequence.sh {}
```

In the example above, we first get a list of all sites that are using this custom upstream, then pass each site ID as a task into a job queue managed by GNU Parallel. These processes will be managed in the background, and will run 50 workers at the same time - each taking a new task until the job queue is empty.

```shell
Finished site-demo-288 in 4.85 minutes
Finished site-demo-292 in 4.50 minutes
Finished site-demo-293 in 4.56 minutes
Finished site-demo-286 in 5.16 minutes
Finished site-demo-279 in 5.75 minutes
Finished site-demo-283 in 5.71 minutes
Finished site-demo-277 in 6.25 minutes
Finished site-demo-290 in 5.58 minutes
Finished site-demo-287 in 7.00 minutes
```

You can see in the output above that because jobs may finish at different times, the deployments are completed out of order. One important change we made was adding a timeout wrapper to each deployment as to not hold keep the build container running if there was some kind of processing issue, killing the process after a specific amount of time.

```bash
# Timeout after 15 minutes.
timeout 15m ./deploy-sequence.sh $1
```



**Reference Links**

 - https://opensource.com/article/18/5/gnu-parallel
 - https://www.gnu.org/software/parallel/parallel_tutorial.html