This is _wethook_, a webhook-triggered task runner.

### Usage
#### Running the server
```
php wethook.php
```
Add the switch `-h` to get help about command line usage.
#### The web UI
The web UI provides some info and stats about the server. Just visit `http://ipaddr:port/` in a browser, where `hostname:port` is the listening ip address and port of the server.

#### Configuration
Configuration is done in the file `config/config.php`.
In case when the file does not exist, it must be manually created.
In order to make modifications take effect, the server must be restarted.

The file `config/config.php.dist` may serve as an example or template. 

### Writing hook scripts
Writing hook scripts is the same as writing any other scripts. The following environment variables are available:
  * `WETHOOK_DRIVER` The identifier of the driver.
  * `WETHOOK_EVENT` The name of the event.
  * `WETHOOK_TARGET` The URL of the target resource that triggered the event.
  * `WETHOOK_DATA` Any other data the event may carry, as a JSON string.

### TODO
  * Enqueue tasks in different queues according to their working directories.
