<?php

namespace Drupal\usebb2drupal;

use Drupal\Core\State\StateInterface;
use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Database\Database;
use Drupal\usebb2drupal\Exception\InvalidSourcePathException;
use Drupal\usebb2drupal\Exception\InvalidConfigFileException;
use Drupal\usebb2drupal\Exception\MissingDatabaseTablesException;
use Drupal\usebb2drupal\Exception\MissingLanguagesException;
use \DirectoryIterator;

/**
 * UseBB info service class.
 */
class UseBBInfo implements UseBBInfoInterface {
  const DEFAULT_LANGCODE = 'en';
  const DEFAULT_ENCODING = 'ISO-8859-1';
  const DEFAULT_TEXT_DIRECTION = 'ltr';

  protected $sourcePath;
  protected $databaseConfig;
  protected $config;
  protected $languages;
  protected $migrationList;
  protected $publicUrls;

  /**
   * {@inheritdoc}
   */
  public function __construct(StateInterface $state) {
    $this->sourcePath = $state->get('usebb2drupal.source_path');
    if (empty($this->sourcePath)) {
      // Avoid exception upon module installation.
      return;
    }

    $config_file = $this->sourcePath . '/config.php';

    // config.php must exist and be readable.
    if (!file_exists($config_file) || !is_readable($config_file)) {
      throw new InvalidSourcePathException(format_string('Source path @path is incorrect.', ['@path' => $this->sourcePath]));
    }

    // There is a check in config.php that will call exit() when this doesn;t
    // exist.
    if (!defined('INCLUDED')) {
      define('INCLUDED', TRUE);
    }

    ob_start();
    require $config_file;
    ob_end_clean();

    // The DB credentials and config array must exist.
    if (!isset($dbs) || !is_array($dbs) || !isset($conf) || !is_array($conf)) {
      throw new InvalidConfigFileException('The config.php file does not contain actual UseBB configuration.');
    }

    $this->databaseConfig = $dbs;
    $this->config = $conf;

    $this->migrationList = $state->get('usebb2drupal.migration_list');
    $this->publicUrls = $state->get('usebb2drupal.public_urls', []);
  }

  /**
   * {@inheritdoc}
   */
  public function getDatabase() {
    try {
      $database = Database::getConnection('default', 'usebb2drupal');
    }
    catch (ConnectionNotDefinedException $e) {
      $db_spec = [
        'driver' => 'mysql',
        'database' => $this->databaseConfig['dbname'],
        'username' => $this->databaseConfig['username'],
        'password' => $this->databaseConfig['passwd'],
        'host' => $this->databaseConfig['server'],
        'prefix' => $this->databaseConfig['prefix'],
      ];
      Database::addConnectionInfo('usebb2drupal', 'default', $db_spec);
      $database = Database::getConnection('default', 'usebb2drupal');

      // Check the tables and prefix are okay.
      if (!$database->schema()->tableExists('members')) {
        throw new MissingDatabaseTablesException('Wrong table prefix or no database tables found.');
      }
    }
    return $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig($key) {
    return isset($this->config[$key]) ? $this->config[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguages() {
    if (!isset($this->languages)) {
      $language_dir = $this->sourcePath . '/languages';
      if (!file_exists($language_dir) || !is_dir($language_dir)) {
        throw new MissingLanguagesException('No language files found in the UseBB path.');
      }

      $languages = [];
      foreach (new DirectoryIterator($language_dir) as $file) {
        $matches = NULL;
        if (!preg_match('#^lang_(\w+)\.php$#', $file->getFilename(), $matches)) {
          continue;
        }
        list(, $language) = $matches;

        ob_start();
        require $file->getPathname();
        ob_end_clean();

        $languages[$language] = [
          'language_code' => isset($lang['language_code']) ? strtolower($lang['language_code']) : self::DEFAULT_LANGCODE,
          'character_encoding' => isset($lang['character_encoding']) ? strtoupper($lang['character_encoding']) : self::DEFAULT_ENCODING,
          'text_direction' => isset($lang['text_direction']) ? strtolower($lang['text_direction']) : self::DEFAULT_TEXT_DIRECTION,
        ];
        unset($lang);
      }

      if (empty($languages)) {
        throw new MissingLanguagesException('No language files found in the UseBB path.');
      }
      $this->languages = $languages;
    }
    return $this->languages;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageCode($language = NULL) {
    if ($language === NULL) {
      $language = $this->getConfig('language');
    }
    $languages = $this->getLanguages();
    return isset($languages[$language]['language_code']) ? $languages[$language]['language_code'] : self::DEFAULT_LANGCODE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEncoding($language = NULL) {
    if ($language === NULL) {
      $language = $this->getConfig('language');
    }
    $languages = $this->getLanguages();
    return isset($languages[$language]['character_encoding']) ? $languages[$language]['character_encoding'] : self::DEFAULT_ENCODING;
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicUrls() {
    return $this->publicUrls;
  }

  /**
   * {@inheritdoc}
   */
  public function getTopicFromPost($id) {
    return $this->getDatabase()
      ->select('posts', 'p')
      ->fields('p', ['topic_id'])
      ->condition('p.id', $id)
      ->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getMigrationList() {
    return $this->migrationList;
  }

  /**
   * {@inheritdoc}
   */
  public function isMigrated($type) {
    return in_array('usebb_' . $type, $this->migrationList);
  }

}
