import logging
import os
import timeit
import threading


def terminus_seq(site_name):
    dev_site = '{site_name}.dev'.format(site_name=site_name)
    sep = ';'
    seq_list = [
        'terminus site:upstream:clear-cache {site}'.format(site=site_name),
        'terminus upstream:update:status {site}'.format(site=dev_site),
        'terminus upstream:updates:apply {site}'.format(site=dev_site),
        'terminus drush {site} -- updb -y'.format(site=dev_site),
        'terminus env:clear-cache {site}'.format(site=dev_site),
    ]

    if env != 'dev':
        site_env = '{site_name}.{env}'.format(site_name=site_name, env=env)
        seq_list.append(
            'terminus env:deploy {site} --cc --updatedb --note "{note}"'.format(site=site_env, note=note),
        )

    cmd = sep.join(seq_list)
    return cmd


def terminus_deploy(name):
    logging.info("Starting thread: {name}".format(name=name))
    # Time thread
    start = timeit.default_timer()

    # Run commands
    commands = terminus_seq(name)
    os.system(commands)

    # Log time
    time = round(timeit.default_timer() - start, 3)
    logging.info("Thread {name}: {time} sec".format(name=name, time=time))


def threader():
    while sites:
        # get the job from the front of the queue
        terminus_deploy(sites.pop())


# Main process
if __name__ == "__main__":
    time_format = "%(asctime)s: %(message)s"
    logging.basicConfig(format=time_format, level=logging.INFO, datefmt="%H:%M:%S")

    # Make some variables global for threads.
    global sites
    global note
    global env

    # Assign vars
    sites = os.environ["SITES"].split("\n")
    note = os.environ["NOTE"]
    env = "test"  # Currently will only target test.

    # Concurrently run through all sites.

    # Utilize 8 workers
    for x in range(8):
        d = threading.Thread(name="Terminus daemon", target=threader)
        # this ensures the thread will die when the main thread dies
        # can set d.daemon to False if you want it to keep running
        d.daemon = True
        d.start()

