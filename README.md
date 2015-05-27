# Daemon.php
An abstract class that provides methods to daemonise a Linux/Unix PHP command-line script

# Usage
Below is the minimum code you need to use the functionality of Daemon.php. The steps you need to take are:
1. Import the code using require().
2. Create a global to store the name of the lock file (if you wish to use a lock file).
3. Inherit you application class from Daemon, using the extends keyword.
4. In your class constructor, set the application name by assigning to $this->appname.
5. Create a public static class method called signalHandler(), that will gracefully exit you program, when a signal is received.
6. Create a public class method called start(), that contains your application code.
7. Then external to your class, do this:
        * Instantiate an instance of your class.
        * Call the static method setLock(), to create a lock file (if you wish to use a lock file).
        * Call daemonise().
        * Call installSignalHandlers("DT_Loggingd"), passing the name of your derived class.
        * Call start().

# An example is shown below:
```php
<?php
require("Daemon.php");
$pidfile = "mylock.pid";

// create the application class
class MyClass extends Daemon
{
    public function __construct()
    {
        $this->appname = cli_get_process_title();
    }

    public static function signalHandler( $signo )
    {
        global $pidfile;
        if ($signo == SIGTERM || $signo == SIGHUP || $signo == SIGINT)
        {
            MyClass::unsetLock($pidfile);
            exit(0);
        }
    }
    
    public function start()
    {
    }
}

// start the daemon application
$myObject = new MyClass();
MyClass::setLock($pidfile);
$myObject->daemonise();
$myObject->installSignalHandlers("MyClass");
$myObject->start();
exit(0);
?>
```

