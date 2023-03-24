# OSU Migrations

Contains submodules for D7 to D9 migrations

## OSU Migrations Files

To properly migrate files open up the upgrade_d7_files.yml and set the source_base_path to either the public url of the
drupal or set the file path to the root of the distro.

Currently, it is set to the base of this installation so one could copy the files into the container and run the
migration.

## Migrations:

Migrations need to be run in a specific order.

1. ### User Accounts

   This runs every migration require to bring in user accounts and their CAS account linking.

   `drush migrate:import --tag='OSU Accounts'`

2. ### Files
   Migrate all the known File Entities.

   `drush migrate:import upgrade_d7_files`

3. ### Media
   Migrate all the Media entities.

   `drush migrate:import --tag='OSU Media'`

4. ### Paragraphs to Layout Builder
   Paragraphs to Layout Builder migration requires two steps:

    1. #### Run all the Paragraphs to LB migrations first.
       `drush migrate:import --tag='OSU Paragraphs'`

    3. #### Run all the content migrations that used Paragraphs.
       `drush migrate:import --tag='Layout content' --force`
