from celery import Celery
import os

app = Celery('hello', broker='amqp://guest@localhost//')

@app.task
def hello():
    return 'hello world'