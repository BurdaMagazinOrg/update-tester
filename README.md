
**`Still work in progress`**

# Update Tester

This helper project to test Drupal 8 updates.

## Getting Started

This project should be installed globally with composer. It provides functionality to test Drupal 8 site update.
Test functionality will clone Drupal 8 site and execute composer update on it with latest minor versions.
After that Drupal 8 update hooks will be executed on cloned site.

### Prerequisites

This project depends on composer and drush. They should be installed on system.

### Installing

To install project, just require it globally:

```
composer global require "burdamagazinorg/update-tester"
```

To run it, ```robo``` command it require to be globally available. That why it should be ensured that global composer scripts are available. If that's not case you can simply add it by executing following command:
```
export PATH="$HOME/.composer/vendor/bin:$PATH"
```

**NOTE:** If you are using ```robo``` for your project, you have to ensure it's executed from your project scope. For example:
```
/my/project/path/vendor/bin/robo
```

## Running the update test

To execute test for your installed Drupal 8 site, you can execute following command:
```
update-tester.php test:update /source/project/path /clone/destination/path --db-name=drupal_clone --db-username='user_clone' --db-password='password_clone'
```
