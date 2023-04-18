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

2. ### Media
   Migrate all the Files and Media entities.

   `drush migrate:import --tag='OSU Media'`

3. ### Taxonomy Tags
   `drush migrate:import --tag='OSU Taxonomy'`

4. ### Paragraphs to Layout Builder
   Paragraphs to Layout Builder migration requires two steps:

    1. #### Run all the Paragraphs to LB migrations first.
       `drush migrate:import --tag='OSU Paragraphs'`

    2. #### Run all the content migrations that used Paragraphs.
       `drush migrate:import --tag='Layout content' --force`

5. ### Feature Story/Articles to Story
   `drush migrate:import --tag='Feature Story'`

6. ### Site Specific Migrations
   Create a new module under site_migrations and name it 'osu_migrations_site' where 'site' is some short version of the
   site working with. This will contain any configurations and migrations for this specific site that would not be
   migrations that the rest of the distribution would go.

   Enable your new module and run any of the migrations you define.

7. ### Migrate the URL Aliases
   `drush migrate:import --tag='OSU Alias'`

8. ### The Last content migration to run is Users to Profiles
   `drush migrate:import --tag='OSU Drupal Profile'`

9. ### Menus
   `drush migrate:import --tag='OSU Menus'`

10. ### Blocks
    Migrate all custom blocks and where they were placed.
    `drush migrate:import --tag='OSU Block'`

