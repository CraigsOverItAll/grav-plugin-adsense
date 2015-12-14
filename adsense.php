<?php
/**
 * AdSense v1.0.1
 *
 * This plugin enables to use AdSense inside a document
 * to be rendered by Grav.
 *
 * Licensed under the MIT Version 4 licenses, see LICENSE.
 * http://cookie-soft.de/license
 *
 * @package     AdSense
 * @version     1.0.1
 * @link        <https://github.com/muuvmuuv/grav-plugin-adsense>
 * @author      Marvin Heilemann <marvin.heilemann@cookie-soft.de>
 * @copyright   2015, Marvin Heilemann
 * @license     <http://opensource.org/licenses/MIT>
 */

namespace Grav\Plugin;

use Grav\Common\Data\Data;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use Grav\Common\Twig\Twig;
use RocketTheme\Toolbox\Event\Event;

/**
 * AdSensePlugin
 *
 * This plugin enables to use AdSense inside a document
 * to be rendered by Grav.
 */
class AdSensePlugin extends Plugin {

  /**
   * Return a list of subscribed events.
   *
   * @return array A list of events.
   */
  public static function getSubscribedEvents()
  {
    return [
      'onPluginsInitialized' => ['onPluginsInitialized', 0]
    ];
  }

  /**
   * Initialize configuration
   */
  public function onPluginsInitialized()
  {
    if ($this->isAdmin()) {
      $this->active = false;
      return;
    }

    if ($this->config->get('plugins.adsense.enabled')) {
      $this->enable([
        'onPageContentRaw'    => ['onPageContentRaw', 0],
        'onTwigSiteVariables' => ['onTwigSiteVariables', 0],
        'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
      ]);
    }
  }

  /**
   * Add content after page content was read into the system.
   *
   * @param  Event  $event An event object, when `onPageContentRaw` is fired.
   */
  public function onPageContentRaw(Event $event)
  {
    /** @var Page $page */
    $page   = $event['page'];
    /** @var Twig $twig */
    $twig   = $this->grav['twig'];
    /** @var Data $config */
    $config = $this->mergeConfig($page, TRUE);
    /** @var Sandbox $sandy */
    $sandy  = $config->get('sandbox');

    /** collect the adsense data and options */
    $data       = $this->config->get('plugins.adsense.adsense.data');
    $options    = $this->config->get('plugins.adsense.adsense.options');
    $type       = $options['type'];
    $direction  = $options['direction'];
    $resource   = $options['resource'];

    /** check them */
    $this->checkType($type);
    $this->checkDirection($direction);

    /** collect the modular config */
    $modular_type = $config->get('type');
    $modular_direction = $config->get('direction');

    if ($config->get('enabled') && $config->get('active')) {

      /** check if the sandbox is active */
      $sandy ?
        $twig->twig_vars['adsense_sandy'] = true :
        $twig->twig_vars['adsense_sandy'] = false ;

      /** inserting configuration into twig_vars */
      $twig->twig_vars['adsense_type']      = $modular_type ? $modular_type : $type;
      $twig->twig_vars['adsense_direction'] = $modular_direction ? $modular_direction : $direction;
      $twig->twig_vars['adsense_client']    = $data['client'];
      $twig->twig_vars['adsense_slot']      = $data['slot'];

      /** redering the template and insert vars */
      $html = $twig->processTemplate('partials/adsense.html.twig', array(
          'adsense_sandy'     => $twig->twig_vars['adsense_sandy'],
          'adsense_type'      => $twig->twig_vars['adsense_type'],
          'adsense_direction' => $twig->twig_vars['adsense_direction'],
          'adsense_client'    => $twig->twig_vars['adsense_client'],
          'adsense_slot'      => $twig->twig_vars['adsense_slot']
      ));

      echo $html;
    }
  }

  /**
   * Add style and script to page
   */
  public function onTwigSiteVariables()
  {
    $adsense    = $this->config->get('plugins.adsense');
    $sandy      = $adsense['sandbox'];
    $options    = $adsense['adsense']['options'];
    $priority   = $options['priority'];
    $pipeline   = $options['pipeline'];
    $load       = $options['load'];
    $resource   = $options['resource'];

    /** adding the style/script to the assets */
    $sandy ? : $this->grav['assets']->addJs($resource, $priority, $pipeline, $load); // check sandy
    $sandy ? : $this->grav['assets']->addJs('plugin://adsense/assets/js/adsense.js', $priority, $pipeline, $load); // check sandy
    $this->grav['assets']->addCss('plugin://adsense/assets/css/adsense.css', $priority, $pipeline);
  }

  /**
   * Add current directory to twig lookup paths.
   */
  public function onTwigTemplatePaths()
  {
      $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
  }

  /**
   * Check the type
   *
   * @param  string $type of the adsense.
   */
  private function checkType($type)
  {
    if (empty($type)){
        throw new \InvalidArgumentException('The AdSense type variable value must be defined. At the moment it is empty. If you are overriding the default configuration, please define the type variable too with a valid value.');
    }
    if (!preg_grep("/" . $type . "/i", array(
        "banner",
        "fixed",
    ))){
        throw new \InvalidArgumentException(sprintf('The AdSense type variable value must be one of "banner" or "fixed". You gave "%s"', $type));
    }
  }

  /**
   * Check the direction
   *
   * @param  string $direction of the adsense.
   */
  private function checkDirection($direction)
  {
    if (empty($direction)){
        throw new \InvalidArgumentException('The AdSense direction variable value must be defined. At the moment it is empty. If you are overriding the default configuration, please define the type variable too with a valid value.');
    }
    if (!preg_grep("/" . $direction . "/i", array(
        "left",
        "top",
        "bottom",
        "right"
    ))){
        throw new \InvalidArgumentException(sprintf('The AdSense direction variable value must be one direction like "left", "right", "top" or "bottom". You gave "%s"', $direction));
    }
  }
}
