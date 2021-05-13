<?php

namespace Ox\Core\Calendar\ICalcreator;

/**
 * class for calendar component VALARM
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since  2.5.1 - 2008-10-12
 */
class valarm extends calendarComponent {
  var $action;
  var $attach;
  var $attendee;
  var $description;
  var $duration;
  var $repeat;
  var $summary;
  var $trigger;
  var $xprop;

  /**
   * constructor for calendar component VALARM object
   *
   * @param array $config
   *
   * @return void
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.8.2 - 2011-05-01
   */
  function __construct($config = array()) {
    parent::__construct();

    $this->action      = '';
    $this->attach      = '';
    $this->attendee    = '';
    $this->description = '';
    $this->duration    = '';
    $this->repeat      = '';
    $this->summary     = '';
    $this->trigger     = '';
    $this->xprop       = '';

    if (defined('ICAL_LANG') && !isset($config['language'])) {
      $config['language'] = ICAL_LANG;
    }
    if (!isset($config['allowEmpty'])) {
      $config['allowEmpty'] = true;
    }
    if (!isset($config['nl'])) {
      $config['nl'] = "\r\n";
    }
    if (!isset($config['format'])) {
      $config['format'] = 'iCal';
    }
    if (!isset($config['delimiter'])) {
      $config['delimiter'] = DIRECTORY_SEPARATOR;
    }
    $this->setConfig($config);
  }

  /**
   * create formatted output for calendar component VALARM object instance
   *
   * @param array $xcaldecl
   *
   * @return string
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.5.1 - 2008-10-22
   */
  function createComponent(&$xcaldecl) {
    $objectname = $this->_createFormat();
    $component  = $this->componentStart1 . $objectname . $this->componentStart2 . $this->nl;
    $component  .= $this->createAction();
    $component  .= $this->createAttach();
    $component  .= $this->createAttendee();
    $component  .= $this->createDescription();
    $component  .= $this->createDuration();
    $component  .= $this->createRepeat();
    $component  .= $this->createSummary();
    $component  .= $this->createTrigger();
    $component  .= $this->createXprop();
    $component  .= $this->componentEnd1 . $objectname . $this->componentEnd2;

    return $component;
  }
}
