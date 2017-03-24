
**`Still work in progress`**

# Update Tester

This is helper tool for testing of Drupal 8 site updates with composer packaging. It's designed to be used in testing environment.

## Getting Started

This project should be installed globally with composer. It provides functionality to test Drupal 8 site update.

Tester script will clone Drupal 8 site (files and database), after that new available versions will be fetched with ```composer outdated``` and composer.json will be updated with new versions. On top of that ```composer update``` will be executed to update packages and modules.
Same script will execute Drupal 8 required update hooks and entity field updates on previously updated code for cloned site.

That will allow early discovery of possible breaking updates for Drupal 8 site.

### Prerequisites

This project depends on composer and drush. They should be installed on system.

Ensure that composer can work without any interruption, that means:
- for private repositories ssh keys are properly set and configuration, so that password is not requested
- authentication token for github.com is provided, because composer will make a lot requests to github and it has to use token in order to get access, otherwise github.com will cut-off requests. For more info take at look at [Composer documentation API rate limit and OAuth tokens](https://getcomposer.org/doc/articles/troubleshooting.md#api-rate-limit-and-oauth-tokens)

### Installing

To install project, just require it globally:

```
composer global require "burdamagazinorg/update-tester"
```

As dependency for this project ```consolidation/robo``` package will be installed.
And in order to run provided scripts, ```robo``` command has to be globally available. That's why it should be ensured that global composer scripts are available in command paths. If it's not already case, it sufficient to execute following command to add global composer scripts path in execution paths:
```
export PATH="$HOME/.composer/vendor/bin:$PATH"
```

**NOTE:** If ```robo``` is used for project, it has to be ensured, that it's executed from project script path. For example:
```
/my/project/path/vendor/bin/robo
```

## Running the update test

To execute update test for your installed Drupal 8 site, you can execute following command:
```
update-tester.php test:update /source/project/path /clone/destination/path --db-name=drupal_clone --db-username='user_clone' --db-password='password_clone'
```

## Travis CI Integration

Ideally update tests should be executed once per day to check, if update still works and if not, what are problems.

In order to do that, [Travis CI cron](https://docs.travis-ci.com/user/cron-jobs/) test triggering has to be enabled for branchs where you want to test updates (fe. master and develop).
Next step is to use Travis CI environment variable ```$TRAVIS_EVENT_TYPE``` to distinguish cron run from normal pull request or merge request run, since update tester should be executed only on cron run.
After installation of site is finished or before running default site tests, update tester should be executed. That can be achieved with following statements in travis.yml file:
```
[ "$TRAVIS_EVENT_TYPE" = "cron" ] && composer global require "burdamagazinorg/update-tester" && export PATH="$HOME/.composer/vendor/bin:$PATH" && update-tester.php test:update /source/project/path /clone/destination/path --db-name=drupal_clone --db-username='user_clone' --db-password='password_clone'
```

## Possible improvements

- add rollback functionality for tasks, then they can be combined easier without possibility to break something on failure
- additional refactoring of ```test:update``` command
- add tests for commands and tasks
