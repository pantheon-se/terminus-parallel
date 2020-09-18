# Mass Terminus Deployment

Due to some recent concerns around the completion time of running mass deploys (particularly for very large portfolios), we have recently dove into this headfirst to start looking into some options in how we can improve Terminus performance and layer on some parallel processing and concurrency as well.

## Problem
Terminus is a process-driven, Symfony console application that uses PHP in the background to cURL a series of requests to the Terminus API. There are some major bottlenecks in the default operating mode:

- Current `mass` functions run serially
- Terminus API has request timeouts for bulk requests
- Terminus doesn't allow shared dependencies in plugins
  - Can't redeclare Symfony package that Terminus uses

This makes it (currently) impossible to have a native Terminus-based parallel processing solution, so we need to wrap Terminus commands with an external parallel manager.

## Solutions

There are a few methods that we went through that can be used to provide improved concurrency, but largely these are heavily dependent on the specs of the machine that is running the scripts. 

For example, your Macbook Pro will have 8 cores with 8 available processing threads, while the Github Actions build container has 2 vCPUs and only 1 available job thread - which means even if you had some type of parallel tool, you could only run one task at a time in the build container, but you could utilize your local to expedite the tasks.

We attempted a number of approaches, largely all of them fell into the same issue of "How do we manage multiple jobs that can run asynchronously?", and that's where most of them fell short.


### Python Wrapper
Python has some native capabilities for implementing processing pools and multithreading (though Python isn't really good at this). There was also the attempt to use some kind message queue, and use [Celery](https://github.com/celery/celery) as the processing interface, but once again we're bound to the machine limitations.

### PHP Process Manager
<insert>

### GNU Parallel
> [GNU parallel](https://www.gnu.org/software/parallel/) is a shell tool for executing jobs in parallel using one or more computers. A job can be a single command or a small script that has to be run for each of the lines in the input. The typical input is a list of files, a list of hosts, a list of users, a list of URLs, or a list of tables. A job can also be a command that reads from a pipe. GNU parallel can then split the input and pipe it into commands in parallel.

We used GNU parallel to split each set of terminus steps to speed up builds for customers who have large portfolio of Pantheon sites. The sample set (Org) we used this on was a portfolio of 300 sites on Pantheon that needed to (1) check for an upstream update, (2) make sure the Git mode was enabled, and (3) apply the upstream updates; flush caches; be avaiable for other commands like create backups in an asyncronous manner. All of this needed to be reliable, so the parlor trick of a trailing ampersand in a bash command was not suitable. Hence our adoption of GNU parallel.

There is no Terminus plugin or wrapper because GNU parallel should not be (A) exectuted by PHP itself, and (B) multiple terminus commands may need some manipulation of the dataset (Site Names, Org ID's, etc.). The best fit was have GNU parallel trigger a bash script that outlined all the Terminus steps each site should receive. This allows us to change the steps bash script and leave the GNU parallel action in Github alone and generic enough to scale at the Terminus list of commands level and not the GH Action. This will allow us to copy the recipe over and over per customer and not be complex for their customizations.


### Shell-based, OS managed processes
<insert>


```
Finished purina-demo-207
real	5m37.388s

Finished purina-demo-202
real	6m46.329s

Finished purina-demo-191
real	6m52.334s

Finished purina-demo-190
real	6m30.460s

Finished purina-demo-197
real	7m11.020s
```
