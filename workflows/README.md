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
<insert>

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