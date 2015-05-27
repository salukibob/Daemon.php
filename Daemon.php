<?php

//
// Version: 0.1
// History: 0.1 - Initial commit
// TODO:
// 1. Add some decent documentation
// 2. Add a composer.json for this project, and post to packagist
//

// signal handler function
declare(ticks = 1); // how often to check for signals

abstract class Daemon
{
    private static $lock;
    abstract protected $appname;

    public static function setLock($pathToLockFile)
    {
        self::$lock = fopen($pathToLockFile, 'c');
        if (!flock(self::$lock, LOCK_EX | LOCK_NB)) 
        {
            fclose(self::$lock);
            $errstring = $this->appname . ": already running - exiting\n";
            print($errstring);
            exit(1);
        }
        else
        {
            $pid = posix_getpid();
            fseek(self::$lock, 0);
            ftruncate(self::$lock, 0);
        	fwrite(self::$lock, $pid);
            fflush(self::$lock);
        }
    }

    public static function unsetLock($pathToLockFile)
    {
        if( get_resource_type(self::$lock) === 'stream' )
        {
            flock(self::$lock, LOCK_UN);
            fclose(self::$lock);
            unlink($pathToLockFile);
        }
    }

    public function daemonise()
    {
        // don't daemonise if the foreground option was passed
        if( array_key_exists( "foreground", $this->config ) && $this->config['foreground'] === 'true' )
        {
            $str = $this->appname . ": Running in foreground...\n";
            print($str);
            return;
        }

        // fork and deal with the parent
        switch ($pid = pcntl_fork()) 
        {
        case -1:
            $errstring = $this->appname . ": unable to fork - exiting\n";
            print($errstring);
            exit(1);
            break;
        case 0: // this is the child process - continue
            break;
        default: // otherwise this is the parent process
            // write out the child pid to the lock file - as per convention
            fseek(self::$lock, 0);
            ftruncate(self::$lock, 0);
        	fwrite(self::$lock, $pid);
            fflush(self::$lock);
            exit(0);    
        }
 
        // become our own session leader        
        if (posix_setsid() === -1)
        {
            $errstring = $this->appname . ": could not setsid";
            print($errstring);
            exit(1);
        }

        // change the current working directory to root 
        chdir("/");

        // set the umask to the default
        umask(0);

        // close the inherited file handles, and open some new ones
        // TODO: Add option to set STDERR to logfile for PHP errors
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);
        $stdIn = fopen('/dev/null', 'r'); // set fd/0
        $stdOut = fopen('/dev/null', 'w'); // set fd/1
        $stdErr = fopen('php://stdout', 'w'); // a hack to duplicate fd/1 to 2
    }

    abstract static public function signalHandler( $signo );

    public function installSignalHandlers( $childClassName )
    {
        pcntl_signal(SIGTERM, array($childClassName, "signalHandler"));
        pcntl_signal(SIGHUP,  array($childClassName, "signalHandler"));
        pcntl_signal(SIGINT,  array($childClassName, "signalHandler"));
    }

    abstract public function start();
}

?>
