INTRODUCTION
------------

UseBB2Drupal extends upon the core migrate module to provide migration from
legacy UseBB 1 forums to Drupal 8. It converts users, categories, forums,
topics, posts and IP address bans without overwriting existing Drupal content.
It also translates internal forum links to the new Drupal paths.


INSTALLATION
------------

Simply enable the module at the Extend page or use drush:

$ drush en usebb2drupal


CONFIGURATION/MIGRATION
-----------------------

There is no configuration to be made, but the migration can be started from
admin/structure/forum/migrate-usebb. Specify UseBB's installation path and
click 'Start migration'.

For user signatures to be migrated, you must first download and install the
contrib signature module, found at https://www.drupal.org/project/signature.

Selecting "Structure and content without users" will cause all topics and posts
to be authored by user 0 (Anonymous). This is suitable if you want to migrate
the contents but not the member base, e.g. for archived or dead forums. The
original poster's username will be shown as "Name for Anonymous".

If you want to convert internal links in forum descriptions, topics, posts and
user signatures, you need to provide the public URLs on which the UseBB forum
can or could be accessed in the past. Without the URL(s), the translation is
disabled.

Please test the migration on a private or local environment before executing
it on a live website. All forums will become public, even when UseBB permissions
defined otherwise.


TROUBLESHOOTING
---------------

The migration batch does not show detailed error/warning messages. If a
migration fails, check the database error log at admin/reports/dblog. Please
report any problems in the project's issue tracker.


FAQ
---

Q: What UseBB data is (currently) not migrated?

A: User ranks, avatars, subscriptions, bad words, username/email bans, forum
   access permissions, moderator permissions, and smilies.
   Specific UseBB user and global board settings are not migrated, since not
   all UseBB functionality is being replicated in Drupal. Once more contributed
   modules are released, some of this functionality may become available with
   later versions of UseBB2Drupal.

Q: What happens with BBCode syntax?

A: All BBCode is converted to HTML with a custom text format 'Forum HTML'.
   However, due to limitations in the format, color tags are stripped and size
   tags with at least 12pt are translated to h2 elements, leaving but one
   custom text size (for now).

Q: Why do I need to specify the full path to the UseBB installation?

A: UseBB source files are being read to deduce enabled languages and board
   configuration. IP address bans are not migrated whenever IP address banning
   has been disabled in UseBB.


DEVELOPING
----------

Unit tests have been written for the Migrate process plugins. Run them using

  cd core
  ../vendor/phpunit/phpunit/phpunit --group usebb2drupal


MAINTAINERS
-----------

Current maintainers:

  * Dietrich Moerman (dietr_ch) - https://www.drupal.org/u/dietr_ch
