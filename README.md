# OSU Migrations

Contains submodules for D7 to D9 migrations

## OSU Migrations Files

To properly migrate files open up the upgrade_d7_files.yml and set the source_base_path to either the public url of the
drupal or set the file path to the root of the distro.

Currently, it is set to the base of this installation so one could copy the files into the container and run the
migration.

## Migrations:

### User Accounts

`drush migrate:import --tag='OSU Accounts'`
This runs every migration require to bring in user accounts and their CAS account linking.

### Files

`drush migrate:import upgrade_d7_files`

### Media

`drush migrate:import --tag='OSU Media'`

### Paragraphs to Layout Builder

#### Run all the Paragraphs to LB migrations first.

`drush migrate:import --tag='OSU Paragraphs'`

#### Run all the content migrations that used Paragraphs.

`drush migrate:import --tag='Layout content' --force`
