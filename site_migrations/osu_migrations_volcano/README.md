# Template for site-specific migrations

Copy this directory and rename `template` with a short name of the site.

Migrations specific to that site will be included here. Any custom Source/Process plugins can be created and referenced
here.

## Checklist

It is recommended to create lists here for the site and update them during the migration process.

### Example List

#### Node types with Media/Files

- [ ] News
    - Entity Reference to Volcano's

#### Node types with Paragraph fields

Listing the dependencies of the type can help you understand what needs to be configured for the migration.

- [ ] fieldtrip_stop
    - Uses Paragraphs
    - Entity reference to content
      - field_picture_with_text
- [ ] Volcano
    - Paragraphs
    - File
    - ER to taxonomy terms

## Paragraph Types

- [x] picture_with_text
- [x] body_with_title
- [ ] fieldtrip_stop
- [ ] teaser_with_text
