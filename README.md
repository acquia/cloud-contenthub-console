# Acquia Cloud Content Hub Console
Acquia Cloud Content Hub Console provides a command line tool to execute commands on all sites that belong to an Acquia
Cloud subscription. 
Depending on the platform that you create, you can execute the same command on all your Acquia Cloud sites or your 
Acquia Cloud Multi-sites. 

# Installation
Install the package with the latest version of composer:

    composer require acquia/cloud-contenthub-console
    
# Usage
The following are some of the commands that are available to you to be used once deployed to Acquia Cloud:


    ./vendor/bin/commoncli                                                                            ✔  10178  12:19:41
    CommonConsole 0.0.1
    
    Usage:
      command [options] [arguments]
    
    Options:
      -h, --help            Display this help message
      -q, --quiet           Do not output any message
      -V, --version         Display this application version
          --ansi            Force ANSI output
          --no-ansi         Disable ANSI output
      -n, --no-interaction  Do not ask any interactive question
          --uri[=URI]       The url from which to mock a request.
          --bare            Prevents output styling.
      -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
    
    Available commands:
      help                               Displays help for a command
      list                               Lists commands
     ace
      ace:cron:list                      [ace-cl] Lists Scheduled Jobs.
      ace:database:backup:create         [ace-dbcr] Creates database backups.
      ace:database:backup:delete         [ace-dbdel] Deletes database backups.
      ace:database:backup:list           [ace-dbl] Lists database backups.
      ace:database:backup:restore        [ace-dbres] Restores database backups.
     ace-multi
      ace-multi:database:backup:create   [ace-dbcrm] Creates database backups for ACE Multi-site environments.
      ace-multi:database:backup:delete   [ace-dbdelm] Deletes database backups for ACE Multi-site environments.
      ace-multi:database:backup:list     [ace-dblm] Lists database backups for ACE Multi-site environments.
      ace-multi:database:backup:restore  [ace-dbresm] Restores database backups for ACE Multisite environments.
     platform
      platform:create                    [pc] Create a new platform on which to execute common console commands.
      platform:delete                    [pdel] Deletes the specified platform.
      platform:describe                  [pd] Obtain more details about a platform.
      platform:list                      [pl] List available platforms.
      platform:sites                     List available sites registered in the platform.

