<?php

namespace Ox\Core\Calendar\ICalcreator;

use Ox\Core\CClassMap;

/**
 *  abstract class for calendar components
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since  2.9.6 - 2011-05-14
 */
class calendarComponent {
//  component property variables
  var $uid;
  var $dtstamp;

//  component config variables
  var $allowEmpty;
  var $language;
  var $nl;
  var $unique_id;
  var $format;
  var $objName; // created automatically at instance creation
  var $dtzid;   // default (local) timezone
//  component internal variables
  var $componentStart1;
  var $componentStart2;
  var $componentEnd1;
  var $componentEnd2;
  var $elementStart1;
  var $elementStart2;
  var $elementEnd1;
  var $elementEnd2;
  var $intAttrDelimiter;
  var $attributeDelimiter;
  var $valueInit;
//  component xCal declaration container
  var $xcaldecl;

  /**
   * constructor for calendar component object
   *
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.9.6 - 2011-05-17
   */
  function __construct() {
    $this->objName = (isset($this->timezonetype)) ? strtolower($this->timezonetype) : CClassMap::getSN(static::class);
    $this->uid     = array();
    $this->dtstamp = array();

    $this->language   = null;
    $this->nl         = null;
    $this->unique_id  = null;
    $this->format     = null;
    $this->dtzid      = null;
    $this->allowEmpty = true;
    $this->xcaldecl   = array();

    $this->_createFormat();
    $this->_makeDtstamp();
  }
  /*********************************************************************************/
  /**
   * Property Name: ACTION
   */
  /**
   * creates formatted output for calendar component property action
   *
   * @return string
   * @since  2.4.8 - 2008-10-22
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createAction() {
    if (empty($this->action)) {
      return false;
    }
    if (empty($this->action['value'])) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('ACTION') : false;
    }
    $attributes = $this->_createParams($this->action['params']);

    return $this->_createElement('ACTION', $attributes, $this->action['value']);
  }

  /**
   * set calendar component property action
   *
   * @param string $value "AUDIO" / "DISPLAY" / "EMAIL" / "PROCEDURE"
   * @param mixed  $params
   *
   * @return bool
   * @since  2.4.8 - 2008-11-04
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setAction($value, $params = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    $this->action = array('value' => $value, 'params' => iCalUtilityFunctions::_setParams($params));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: ATTACH
   */
  /**
   * creates formatted output for calendar component property attach
   *
   * @return string
   * @since  0.9.7 - 2006-11-23
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createAttach() {
    if (empty($this->attach)) {
      return false;
    }
    $output = null;
    foreach ($this->attach as $attachPart) {
      if (!empty($attachPart['value'])) {
        $attributes = $this->_createParams($attachPart['params']);
        $output     .= $this->_createElement('ATTACH', $attributes, $attachPart['value']);
      }
      elseif ($this->getConfig('allowEmpty')) {
        $output .= $this->_createElement('ATTACH');
      }
    }

    return $output;
  }

  /**
   * set calendar component property attach
   *
   * @param string  $value
   * @param array   $params , optional
   * @param integer $index  , optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.5.1 - 2008-11-06
   */
  function setAttach($value, $params = false, $index = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    iCalUtilityFunctions::_setMval($this->attach, $value, $params, false, $index);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: ATTENDEE
   */
  /**
   * creates formatted output for calendar component property attendee
   *
   * @return string
   * @since  2.9.8 - 2011-05-30
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createAttendee() {
    if (empty($this->attendee)) {
      return false;
    }
    $output = null;
    foreach ($this->attendee as $attendeePart) {                      // start foreach 1
      if (empty($attendeePart['value'])) {
        if ($this->getConfig('allowEmpty')) {
          $output .= $this->_createElement('ATTENDEE');
        }
        continue;
      }
      $attendee1 = $attendee2 = null;
      foreach ($attendeePart as $paramlabel => $paramvalue) {         // start foreach 2
        if ('value' == $paramlabel) {
          $attendee2 .= $paramvalue;
        }
        elseif (('params' == $paramlabel) && (is_array($paramvalue))) { // start elseif
// set attenddee parameters in rfc2445 order
          if (isset($paramvalue['CUTYPE'])) {
            $attendee1 .= $this->intAttrDelimiter . 'CUTYPE=' . $paramvalue['CUTYPE'];
          }
          if (isset($paramvalue['MEMBER'])) {
            $attendee1 .= $this->intAttrDelimiter . 'MEMBER=';
            foreach ($paramvalue['MEMBER'] as $cix => $opv) {
              $attendee1 .= ($cix) ? ', "' . $opv . '"' : '"' . $opv . '"';
            }
          }
          if (isset($paramvalue['ROLE'])) {
            $attendee1 .= $this->intAttrDelimiter . 'ROLE=' . $paramvalue['ROLE'];
          }
          if (isset($paramvalue['PARTSTAT'])) {
            $attendee1 .= $this->intAttrDelimiter . 'PARTSTAT=' . $paramvalue['PARTSTAT'];
          }
          if (isset($paramvalue['RSVP'])) {
            $attendee1 .= $this->intAttrDelimiter . 'RSVP=' . $paramvalue['RSVP'];
          }
          if (isset($paramvalue['DELEGATED-TO'])) {
            $attendee1 .= $this->intAttrDelimiter . 'DELEGATED-TO=';
            foreach ($paramvalue['DELEGATED-TO'] as $cix => $opv) {
              $attendee1 .= ($cix) ? ', "' . $opv . '"' : '"' . $opv . '"';
            }
          }
          if (isset($paramvalue['DELEGATED-FROM'])) {
            $attendee1 .= $this->intAttrDelimiter . 'DELEGATED-FROM=';
            foreach ($paramvalue['DELEGATED-FROM'] as $cix => $opv) {
              $attendee1 .= ($cix) ? ', "' . $opv . '"' : '"' . $opv . '"';
            }
          }
          if (isset($paramvalue['SENT-BY'])) {
            $attendee1 .= $this->intAttrDelimiter . 'SENT-BY="' . $paramvalue['SENT-BY'] . '"';
          }
          if (isset($paramvalue['CN'])) {
            $attendee1 .= $this->intAttrDelimiter . 'CN="' . $paramvalue['CN'] . '"';
          }
          if (isset($paramvalue['DIR'])) {
            $attendee1 .= $this->intAttrDelimiter . 'DIR="' . $paramvalue['DIR'] . '"';
          }
          if (isset($paramvalue['LANGUAGE'])) {
            $attendee1 .= $this->intAttrDelimiter . 'LANGUAGE=' . $paramvalue['LANGUAGE'];
          }
          $xparams = array();
          foreach ($paramvalue as $optparamlabel => $optparamvalue) { // start foreach 3
            if (ctype_digit((string)$optparamlabel)) {
              $xparams[] = $optparamvalue;
              continue;
            }
            if (!in_array($optparamlabel, array('CUTYPE', 'MEMBER', 'ROLE', 'PARTSTAT', 'RSVP', 'DELEGATED-TO', 'DELEGATED-FROM', 'SENT-BY', 'CN', 'DIR', 'LANGUAGE'))) {
              $xparams[$optparamlabel] = $optparamvalue;
            }
          } // end foreach 3
          ksort($xparams, SORT_STRING);
          foreach ($xparams as $paramKey => $paramValue) {
            if (ctype_digit((string)$paramKey)) {
              $attendee1 .= $this->intAttrDelimiter . $paramValue;
            }
            else {
              $attendee1 .= $this->intAttrDelimiter . "$paramKey=$paramValue";
            }
          }      // end foreach 3
        }        // end elseif(( 'params' == $paramlabel ) && ( is_array( $paramvalue )))
      }          // end foreach 2
      $output .= $this->_createElement('ATTENDEE', $attendee1, $attendee2);
    }              // end foreach 1

    return $output;
  }

  /**
   * set calendar component property attach
   *
   * @param string  $value
   * @param array   $params , optional
   * @param integer $index  , optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.6.34 - 2010-12-18
   */
  function setAttendee($value, $params = false, $index = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
// ftp://, http://, mailto:, file://, gopher://, news:, nntp://, telnet://, wais://, prospero://  may exist.. . also in params
    if (false !== ($pos = strpos(substr($value, 0, 9), ':'))) {
      $value = strtoupper(substr($value, 0, $pos)) . substr($value, $pos);
    }
    elseif (!empty($value)) {
      $value = 'MAILTO:' . $value;
    }
    $params2 = array();
    if (is_array($params)) {
      $optarrays = array();
      foreach ($params as $optparamlabel => $optparamvalue) {
        $optparamlabel = strtoupper($optparamlabel);
        switch ($optparamlabel) {
          case 'MEMBER':
          case 'DELEGATED-TO':
          case 'DELEGATED-FROM':
            if (!is_array($optparamvalue)) {
              $optparamvalue = array($optparamvalue);
            }
            foreach ($optparamvalue as $part) {
              $part = trim($part);
              if (('"' == substr($part, 0, 1)) &&
                ('"' == substr($part, -1))) {
                $part = substr($part, 1, (strlen($part) - 2));
              }
              if ('mailto:' != strtolower(substr($part, 0, 7))) {
                $part = "MAILTO:$part";
              }
              else {
                $part = 'MAILTO:' . substr($part, 7);
              }
              $optarrays[$optparamlabel][] = $part;
            }
            break;
          default:
            if (('"' == substr($optparamvalue, 0, 1)) &&
              ('"' == substr($optparamvalue, -1))) {
              $optparamvalue = substr($optparamvalue, 1, (strlen($optparamvalue) - 2));
            }
            if ('SENT-BY' == $optparamlabel) {
              if ('mailto:' != strtolower(substr($optparamvalue, 0, 7))) {
                $optparamvalue = "MAILTO:$optparamvalue";
              }
              else {
                $optparamvalue = 'MAILTO:' . substr($optparamvalue, 7);
              }
            }
            $params2[$optparamlabel] = $optparamvalue;
            break;
        } // end switch( $optparamlabel.. .
      } // end foreach( $optparam.. .
      foreach ($optarrays as $optparamlabel => $optparams) {
        $params2[$optparamlabel] = $optparams;
      }
    }
// remove defaults
    iCalUtilityFunctions::_existRem($params2, 'CUTYPE', 'INDIVIDUAL');
    iCalUtilityFunctions::_existRem($params2, 'PARTSTAT', 'NEEDS-ACTION');
    iCalUtilityFunctions::_existRem($params2, 'ROLE', 'REQ-PARTICIPANT');
    iCalUtilityFunctions::_existRem($params2, 'RSVP', 'FALSE');
// check language setting
    if (isset($params2['CN'])) {
      $lang = $this->getConfig('language');
      if (!isset($params2['LANGUAGE']) && !empty($lang)) {
        $params2['LANGUAGE'] = $lang;
      }
    }
    iCalUtilityFunctions::_setMval($this->attendee, $value, $params2, false, $index);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: CATEGORIES
   */
  /**
   * creates formatted output for calendar component property categories
   *
   * @return string
   * @since  2.4.8 - 2008-10-22
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createCategories() {
    if (empty($this->categories)) {
      return false;
    }
    $output = null;
    foreach ($this->categories as $category) {
      if (empty($category['value'])) {
        if ($this->getConfig('allowEmpty')) {
          $output .= $this->_createElement('CATEGORIES');
        }
        continue;
      }
      $attributes = $this->_createParams($category['params'], array('LANGUAGE'));
      if (is_array($category['value'])) {
        foreach ($category['value'] as $cix => $categoryPart) {
          $category['value'][$cix] = $this->_strrep($categoryPart);
        }
        $content = implode(',', $category['value']);
      }
      else {
        $content = $this->_strrep($category['value']);
      }
      $output .= $this->_createElement('CATEGORIES', $attributes, $content);
    }

    return $output;
  }

  /**
   * set calendar component property categories
   *
   * @param mixed   $value
   * @param array   $params , optional
   * @param integer $index  , optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.5.1 - 2008-11-06
   */
  function setCategories($value, $params = false, $index = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    iCalUtilityFunctions::_setMval($this->categories, $value, $params, false, $index);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: CLASS
   */
  /**
   * creates formatted output for calendar component property class
   *
   * @return string
   * @since  0.9.7 - 2006-11-20
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createClass() {
    if (empty($this->class)) {
      return false;
    }
    if (empty($this->class['value'])) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('CLASS') : false;
    }
    $attributes = $this->_createParams($this->class['params']);

    return $this->_createElement('CLASS', $attributes, $this->class['value']);
  }

  /**
   * set calendar component property class
   *
   * @param string $value  "PUBLIC" / "PRIVATE" / "CONFIDENTIAL" / iana-token / x-name
   * @param array  $params optional
   *
   * @return bool
   * @since  2.4.8 - 2008-11-04
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setClass($value, $params = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    $this->class = array('value' => $value, 'params' => iCalUtilityFunctions::_setParams($params));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: COMMENT
   */
  /**
   * creates formatted output for calendar component property comment
   *
   * @return string
   * @since  2.4.8 - 2008-10-22
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createComment() {
    if (empty($this->comment)) {
      return false;
    }
    $output = null;
    foreach ($this->comment as $commentPart) {
      if (empty($commentPart['value'])) {
        if ($this->getConfig('allowEmpty')) {
          $output .= $this->_createElement('COMMENT');
        }
        continue;
      }
      $attributes = $this->_createParams($commentPart['params'], array('ALTREP', 'LANGUAGE'));
      $content    = $this->_strrep($commentPart['value']);
      $output     .= $this->_createElement('COMMENT', $attributes, $content);
    }

    return $output;
  }

  /**
   * set calendar component property comment
   *
   * @param string  $value
   * @param array   $params , optional
   * @param integer $index  , optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.5.1 - 2008-11-06
   */
  function setComment($value, $params = false, $index = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    iCalUtilityFunctions::_setMval($this->comment, $value, $params, false, $index);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: COMPLETED
   */
  /**
   * creates formatted output for calendar component property completed
   *
   * @return string
   * @since  2.4.8 - 2008-10-22
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createCompleted() {
    if (empty($this->completed)) {
      return false;
    }
    if (!isset($this->completed['value']['year']) &&
      !isset($this->completed['value']['month']) &&
      !isset($this->completed['value']['day']) &&
      !isset($this->completed['value']['hour']) &&
      !isset($this->completed['value']['min']) &&
      !isset($this->completed['value']['sec'])) {
      if ($this->getConfig('allowEmpty')) {
        return $this->_createElement('COMPLETED');
      }
      else {
        return false;
      }
    }
    $formatted  = iCalUtilityFunctions::_format_date_time($this->completed['value'], 7);
    $attributes = $this->_createParams($this->completed['params']);

    return $this->_createElement('COMPLETED', $attributes, $formatted);
  }

  /**
   * set calendar component property completed
   *
   * @param mixed $year
   * @param mixed $month  optional
   * @param int   $day    optional
   * @param int   $hour   optional
   * @param int   $min    optional
   * @param int   $sec    optional
   * @param array $params optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.4.8 - 2008-10-23
   */
  function setCompleted($year, $month = false, $day = false, $hour = false, $min = false, $sec = false, $params = false) {
    if (empty($year)) {
      if ($this->getConfig('allowEmpty')) {
        $this->completed = array('value' => null, 'params' => iCalUtilityFunctions::_setParams($params));

        return true;
      }
      else {
        return false;
      }
    }
    $this->completed = iCalUtilityFunctions::_setDate2($year, $month, $day, $hour, $min, $sec, $params);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: CONTACT
   */
  /**
   * creates formatted output for calendar component property contact
   *
   * @return string
   * @since  2.4.8 - 2008-10-23
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createContact() {
    if (empty($this->contact)) {
      return false;
    }
    $output = null;
    foreach ($this->contact as $contact) {
      if (!empty($contact['value'])) {
        $attributes = $this->_createParams($contact['params'], array('ALTREP', 'LANGUAGE'));
        $content    = $this->_strrep($contact['value']);
        $output     .= $this->_createElement('CONTACT', $attributes, $content);
      }
      elseif ($this->getConfig('allowEmpty')) {
        $output .= $this->_createElement('CONTACT');
      }
    }

    return $output;
  }

  /**
   * set calendar component property contact
   *
   * @param string  $value
   * @param array   $params , optional
   * @param integer $index  , optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.5.1 - 2008-11-05
   */
  function setContact($value, $params = false, $index = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    iCalUtilityFunctions::_setMval($this->contact, $value, $params, false, $index);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: CREATED
   */
  /**
   * creates formatted output for calendar component property created
   *
   * @return string
   * @since  2.4.8 - 2008-10-21
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createCreated() {
    if (empty($this->created)) {
      return false;
    }
    $formatted  = iCalUtilityFunctions::_format_date_time($this->created['value'], 7);
    $attributes = $this->_createParams($this->created['params']);

    return $this->_createElement('CREATED', $attributes, $formatted);
  }

  /**
   * set calendar component property created
   *
   * @param mixed $year   optional
   * @param mixed $month  optional
   * @param int   $day    optional
   * @param int   $hour   optional
   * @param int   $min    optional
   * @param int   $sec    optional
   * @param mixed $params optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.4.8 - 2008-10-23
   */
  function setCreated($year = false, $month = false, $day = false, $hour = false, $min = false, $sec = false, $params = false) {
    if (!isset($year)) {
      $year = date('Ymd\THis', mktime(date('H'), date('i'), date('s') - date('Z'), date('m'), date('d'), date('Y')));
    }
    $this->created = iCalUtilityFunctions::_setDate2($year, $month, $day, $hour, $min, $sec, $params);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: DESCRIPTION
   */
  /**
   * creates formatted output for calendar component property description
   *
   * @return string
   * @since  2.4.8 - 2008-10-22
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createDescription() {
    if (empty($this->description)) {
      return false;
    }
    $output = null;
    foreach ($this->description as $description) {
      if (!empty($description['value'])) {
        $attributes = $this->_createParams($description['params'], array('ALTREP', 'LANGUAGE'));
        $content    = $this->_strrep($description['value']);
        $output     .= $this->_createElement('DESCRIPTION', $attributes, $content);
      }
      elseif ($this->getConfig('allowEmpty')) {
        $output .= $this->_createElement('DESCRIPTION');
      }
    }

    return $output;
  }

  /**
   * set calendar component property description
   *
   * @param string  $value
   * @param array   $params , optional
   * @param integer $index  , optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.6.24 - 2010-11-06
   */
  function setDescription($value, $params = false, $index = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    if ('vjournal' != $this->objName) {
      $index = 1;
    }
    iCalUtilityFunctions::_setMval($this->description, $value, $params, false, $index);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: DTEND
   */
  /**
   * creates formatted output for calendar component property dtend
   *
   * @return string
   * @since  2.9.6 - 2011-05-14
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createDtend() {
    if (empty($this->dtend)) {
      return false;
    }
    if (!isset($this->dtend['value']['year']) &&
      !isset($this->dtend['value']['month']) &&
      !isset($this->dtend['value']['day']) &&
      !isset($this->dtend['value']['hour']) &&
      !isset($this->dtend['value']['min']) &&
      !isset($this->dtend['value']['sec'])) {
      if ($this->getConfig('allowEmpty')) {
        return $this->_createElement('DTEND');
      }
      else {
        return false;
      }
    }
    $formatted = iCalUtilityFunctions::_format_date_time($this->dtend['value']);
    if ((false !== ($tzid = $this->getConfig('TZID'))) &&
      (!isset($this->dtend['params']['VALUE']) || ($this->dtend['params']['VALUE'] != 'DATE')) &&
      !isset($this->dtend['params']['TZID'])) {
      $this->dtend['params']['TZID'] = $tzid;
    }
    $attributes = $this->_createParams($this->dtend['params']);

    return $this->_createElement('DTEND', $attributes, $formatted);
  }

  /**
   * set calendar component property dtend
   *
   * @param mixed  $year
   * @param mixed  $month optional
   * @param int    $day   optional
   * @param int    $hour  optional
   * @param int    $min   optional
   * @param int    $sec   optional
   * @param string $tz    optional
   * @param array params optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.9.6 - 2011-05-14
   */
  function setDtend($year, $month = false, $day = false, $hour = false, $min = false, $sec = false, $tz = false, $params = false) {
    if (empty($year)) {
      if ($this->getConfig('allowEmpty')) {
        $this->dtend = array('value' => null, 'params' => iCalUtilityFunctions::_setParams($params));

        return true;
      }
      else {
        return false;
      }
    }
    $this->dtend = iCalUtilityFunctions::_setDate($year, $month, $day, $hour, $min, $sec, $tz, $params, null, null, $this->getConfig('TZID'));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: DTSTAMP
   */
  /**
   * creates formatted output for calendar component property dtstamp
   *
   * @return string
   * @since  2.4.4 - 2008-03-07
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createDtstamp() {
    if (!isset($this->dtstamp['value']['year']) &&
      !isset($this->dtstamp['value']['month']) &&
      !isset($this->dtstamp['value']['day']) &&
      !isset($this->dtstamp['value']['hour']) &&
      !isset($this->dtstamp['value']['min']) &&
      !isset($this->dtstamp['value']['sec'])) {
      $this->_makeDtstamp();
    }
    $formatted  = iCalUtilityFunctions::_format_date_time($this->dtstamp['value'], 7);
    $attributes = $this->_createParams($this->dtstamp['params']);

    return $this->_createElement('DTSTAMP', $attributes, $formatted);
  }

  /**
   * computes datestamp for calendar component object instance dtstamp
   *
   * @return void
   * @since  2.10.9 - 2011-08-10
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function _makeDtstamp() {
    $d                       = mktime(date('H'), date('i'), (date('s') - date('Z')), date('m'), date('d'), date('Y'));
    $this->dtstamp['value']  = array('year' => date('Y', $d)
    , 'month'                               => date('m', $d)
    , 'day'                                 => date('d', $d)
    , 'hour'                                => date('H', $d)
    , 'min'                                 => date('i', $d)
    , 'sec'                                 => date('s', $d));
    $this->dtstamp['params'] = null;
  }

  /**
   * set calendar component property dtstamp
   *
   * @param mixed $year
   * @param mixed $month  optional
   * @param int   $day    optional
   * @param int   $hour   optional
   * @param int   $min    optional
   * @param int   $sec    optional
   * @param array $params optional
   *
   * @return TRUE
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.4.8 - 2008-10-23
   */
  function setDtstamp($year, $month = false, $day = false, $hour = false, $min = false, $sec = false, $params = false) {
    if (empty($year)) {
      $this->_makeDtstamp();
    }
    else {
      $this->dtstamp = iCalUtilityFunctions::_setDate2($year, $month, $day, $hour, $min, $sec, $params);
    }

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: DTSTART
   */
  /**
   * creates formatted output for calendar component property dtstart
   *
   * @return string
   * @since  2.9.6 - 2011-05-15
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createDtstart() {
    if (empty($this->dtstart)) {
      return false;
    }
    if (!isset($this->dtstart['value']['year']) &&
      !isset($this->dtstart['value']['month']) &&
      !isset($this->dtstart['value']['day']) &&
      !isset($this->dtstart['value']['hour']) &&
      !isset($this->dtstart['value']['min']) &&
      !isset($this->dtstart['value']['sec'])) {
      if ($this->getConfig('allowEmpty')) {
        return $this->_createElement('DTSTART');
      }
      else {
        return false;
      }
    }
    if (in_array($this->objName, array('vtimezone', 'standard', 'daylight'))) {
      unset($this->dtstart['value']['tz'], $this->dtstart['params']['TZID']);
    }
    elseif ((false !== ($tzid = $this->getConfig('TZID'))) &&
      (!isset($this->dtstart['params']['VALUE']) || ($this->dtstart['params']['VALUE'] != 'DATE')) &&
      !isset($this->dtstart['params']['TZID'])) {
      $this->dtstart['params']['TZID'] = $tzid;
    }
    $formatted  = iCalUtilityFunctions::_format_date_time($this->dtstart['value']);
    $attributes = $this->_createParams($this->dtstart['params']);

    return $this->_createElement('DTSTART', $attributes, $formatted);
  }

  /**
   * set calendar component property dtstart
   *
   * @param mixed  $year
   * @param mixed  $month  optional
   * @param int    $day    optional
   * @param int    $hour   optional
   * @param int    $min    optional
   * @param int    $sec    optional
   * @param string $tz     optional
   * @param array  $params optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.6.22 - 2010-09-22
   */
  function setDtstart($year, $month = false, $day = false, $hour = false, $min = false, $sec = false, $tz = false, $params = false) {
    if (empty($year)) {
      if ($this->getConfig('allowEmpty')) {
        $this->dtstart = array('value' => null, 'params' => iCalUtilityFunctions::_setParams($params));

        return true;
      }
      else {
        return false;
      }
    }
    $this->dtstart = iCalUtilityFunctions::_setDate($year, $month, $day, $hour, $min, $sec, $tz, $params, 'dtstart', $this->objName, $this->getConfig('TZID'));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: DUE
   */
  /**
   * creates formatted output for calendar component property due
   *
   * @return string
   * @since  2.4.8 - 2008-10-22
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createDue() {
    if (empty($this->due)) {
      return false;
    }
    if (!isset($this->due['value']['year']) &&
      !isset($this->due['value']['month']) &&
      !isset($this->due['value']['day']) &&
      !isset($this->due['value']['hour']) &&
      !isset($this->due['value']['min']) &&
      !isset($this->due['value']['sec'])) {
      if ($this->getConfig('allowEmpty')) {
        return $this->_createElement('DUE');
      }
      else {
        return false;
      }
    }
    $formatted = iCalUtilityFunctions::_format_date_time($this->due['value']);
    if ((false !== ($tzid = $this->getConfig('TZID'))) &&
      (!isset($this->due['params']['VALUE']) || ($this->due['params']['VALUE'] != 'DATE')) &&
      !isset($this->due['params']['TZID'])) {
      $this->due['params']['TZID'] = $tzid;
    }
    $attributes = $this->_createParams($this->due['params']);

    return $this->_createElement('DUE', $attributes, $formatted);
  }

  /**
   * set calendar component property due
   *
   * @param mixed $year
   * @param mixed $month  optional
   * @param int   $day    optional
   * @param int   $hour   optional
   * @param int   $min    optional
   * @param int   $sec    optional
   * @param array $params optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.4.8 - 2008-11-04
   */
  function setDue($year, $month = false, $day = false, $hour = false, $min = false, $sec = false, $tz = false, $params = false) {
    if (empty($year)) {
      if ($this->getConfig('allowEmpty')) {
        $this->due = array('value' => null, 'params' => iCalUtilityFunctions::_setParams($params));

        return true;
      }
      else {
        return false;
      }
    }
    $this->due = iCalUtilityFunctions::_setDate($year, $month, $day, $hour, $min, $sec, $tz, $params, null, null, $this->getConfig('TZID'));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: DURATION
   */
  /**
   * creates formatted output for calendar component property duration
   *
   * @return string
   * @since  2.4.8 - 2008-10-21
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createDuration() {
    if (empty($this->duration)) {
      return false;
    }
    if (!isset($this->duration['value']['week']) &&
      !isset($this->duration['value']['day']) &&
      !isset($this->duration['value']['hour']) &&
      !isset($this->duration['value']['min']) &&
      !isset($this->duration['value']['sec'])) {
      if ($this->getConfig('allowEmpty')) {
        return $this->_createElement('DURATION', array(), null);
      }
      else {
        return false;
      }
    }
    $attributes = $this->_createParams($this->duration['params']);

    return $this->_createElement('DURATION', $attributes, iCalUtilityFunctions::_format_duration($this->duration['value']));
  }

  /**
   * set calendar component property duration
   *
   * @param mixed $week
   * @param mixed $day    optional
   * @param int   $hour   optional
   * @param int   $min    optional
   * @param int   $sec    optional
   * @param array $params optional
   *
   * @return bool
   * @since  2.4.8 - 2008-11-04
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setDuration($week, $day = false, $hour = false, $min = false, $sec = false, $params = false) {
    if (empty($week)) {
      if ($this->getConfig('allowEmpty')) {
        $week = null;
      }
      else {
        return false;
      }
    }
    if (is_array($week) && (1 <= count($week))) {
      $this->duration = array('value' => iCalUtilityFunctions::_duration_array($week), 'params' => iCalUtilityFunctions::_setParams($day));
    }
    elseif (is_string($week) && (3 <= strlen(trim($week)))) {
      $week = trim($week);
      if (in_array(substr($week, 0, 1), array('+', '-'))) {
        $week = substr($week, 1);
      }
      $this->duration = array('value' => iCalUtilityFunctions::_duration_string($week), 'params' => iCalUtilityFunctions::_setParams($day));
    }
    elseif (empty($week) && empty($day) && empty($hour) && empty($min) && empty($sec)) {
      return false;
    }
    else {
      $this->duration = array('value' => iCalUtilityFunctions::_duration_array(array($week, $day, $hour, $min, $sec)), 'params' => iCalUtilityFunctions::_setParams($params));
    }

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: EXDATE
   */
  /**
   * creates formatted output for calendar component property exdate
   *
   * @return string
   * @since  2.4.8 - 2008-10-22
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createExdate() {
    if (empty($this->exdate)) {
      return false;
    }
    $output = null;
    foreach ($this->exdate as $ex => $theExdate) {
      if (empty($theExdate['value'])) {
        if ($this->getConfig('allowEmpty')) {
          $output .= $this->_createElement('EXDATE');
        }
        continue;
      }
      $content = $attributes = null;
      foreach ($theExdate['value'] as $eix => $exdatePart) {
        $parno     = count($exdatePart);
        $formatted = iCalUtilityFunctions::_format_date_time($exdatePart, $parno);
        if (isset($theExdate['params']['TZID'])) {
          $formatted = str_replace('Z', '', $formatted);
        }
        if (0 < $eix) {
          if (isset($theExdate['value'][0]['tz'])) {
            if (ctype_digit(substr($theExdate['value'][0]['tz'], -4)) ||
              ('Z' == $theExdate['value'][0]['tz'])) {
              if ('Z' != substr($formatted, -1)) {
                $formatted .= 'Z';
              }
            }
            else {
              $formatted = str_replace('Z', '', $formatted);
            }
          }
          else {
            $formatted = str_replace('Z', '', $formatted);
          }
        }
        $content .= (0 < $eix) ? ',' . $formatted : $formatted;
      }
      $attributes .= $this->_createParams($theExdate['params']);
      $output     .= $this->_createElement('EXDATE', $attributes, $content);
    }

    return $output;
  }

  /**
   * set calendar component property exdate
   *
   * @param array exdates
   * @param array   $params , optional
   * @param integer $index  , optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.5.1 - 2008-11-05
   */
  function setExdate($exdates, $params = false, $index = false) {
    if (empty($exdates)) {
      if ($this->getConfig('allowEmpty')) {
        iCalUtilityFunctions::_setMval($this->exdate, null, $params, false, $index);

        return true;
      }
      else {
        return false;
      }
    }
    $input = array('params' => iCalUtilityFunctions::_setParams($params, array('VALUE' => 'DATE-TIME')));
    /* ev. check 1:st date and save ev. timezone **/
    iCalUtilityFunctions::_chkdatecfg(reset($exdates), $parno, $input['params']);
    iCalUtilityFunctions::_existRem($input['params'], 'VALUE', 'DATE-TIME'); // remove default parameter
    foreach ($exdates as $eix => $theExdate) {
      if (iCalUtilityFunctions::_isArrayTimestampDate($theExdate)) {
        $exdatea = iCalUtilityFunctions::_timestamp2date($theExdate, $parno);
      }
      elseif (is_array($theExdate)) {
        $exdatea = iCalUtilityFunctions::_date_time_array($theExdate, $parno);
      }
      elseif (8 <= strlen(trim($theExdate))) // ex. 2006-08-03 10:12:18
      {
        $exdatea = iCalUtilityFunctions::_date_time_string($theExdate, $parno);
      }
      if (3 == $parno) {
        unset($exdatea['hour'], $exdatea['min'], $exdatea['sec'], $exdatea['tz']);
      }
      elseif (isset($exdatea['tz'])) {
        $exdatea['tz'] = (string)$exdatea['tz'];
      }
      if (isset($input['params']['TZID']) ||
        (isset($exdatea['tz']) && !iCalUtilityFunctions::_isOffset($exdatea['tz'])) ||
        (isset($input['value'][0]) && (!isset($input['value'][0]['tz']))) ||
        (isset($input['value'][0]['tz']) && !iCalUtilityFunctions::_isOffset($input['value'][0]['tz']))) {
        unset($exdatea['tz']);
      }
      $input['value'][] = $exdatea;
    }
    if (0 >= count($input['value'])) {
      return false;
    }
    if (3 == $parno) {
      $input['params']['VALUE'] = 'DATE';
      unset($input['params']['TZID']);
    }
    iCalUtilityFunctions::_setMval($this->exdate, $input['value'], $input['params'], false, $index);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: EXRULE
   */
  /**
   * creates formatted output for calendar component property exrule
   *
   * @return string
   * @since  2.4.8 - 2008-10-22
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createExrule() {
    if (empty($this->exrule)) {
      return false;
    }

    return $this->_format_recur('EXRULE', $this->exrule);
  }

  /**
   * set calendar component property exdate
   *
   * @param array   $exruleset
   * @param array   $params , optional
   * @param integer $index  , optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.5.1 - 2008-11-05
   */
  function setExrule($exruleset, $params = false, $index = false) {
    if (empty($exruleset)) {
      if ($this->getConfig('allowEmpty')) {
        $exruleset = null;
      }
      else {
        return false;
      }
    }
    iCalUtilityFunctions::_setMval($this->exrule, iCalUtilityFunctions::_setRexrule($exruleset), $params, false, $index);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: FREEBUSY
   */
  /**
   * creates formatted output for calendar component property freebusy
   *
   * @return string
   * @since  2.4.8 - 2008-10-22
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createFreebusy() {
    if (empty($this->freebusy)) {
      return false;
    }
    $output = null;
    foreach ($this->freebusy as $freebusyPart) {
      if (empty($freebusyPart['value'])) {
        if ($this->getConfig('allowEmpty')) {
          $output .= $this->_createElement('FREEBUSY');
        }
        continue;
      }
      $attributes = $content = null;
      if (isset($freebusyPart['value']['fbtype'])) {
        $attributes .= $this->intAttrDelimiter . 'FBTYPE=' . $freebusyPart['value']['fbtype'];
        unset($freebusyPart['value']['fbtype']);
        $freebusyPart['value'] = array_values($freebusyPart['value']);
      }
      else {
        $attributes .= $this->intAttrDelimiter . 'FBTYPE=BUSY';
      }
      $attributes .= $this->_createParams($freebusyPart['params']);
      $fno        = 1;
      $cnt        = count($freebusyPart['value']);
      foreach ($freebusyPart['value'] as $periodix => $freebusyPeriod) {
        $formatted = iCalUtilityFunctions::_format_date_time($freebusyPeriod[0]);
        $content   .= $formatted;
        $content   .= '/';
        $cnt2      = count($freebusyPeriod[1]);
        if (array_key_exists('year', $freebusyPeriod[1]))      // date-time
        {
          $cnt2 = 7;
        }
        elseif (array_key_exists('week', $freebusyPeriod[1]))  // duration
        {
          $cnt2 = 5;
        }
        if ((7 == $cnt2) &&    // period=  -> date-time
          isset($freebusyPeriod[1]['year']) &&
          isset($freebusyPeriod[1]['month']) &&
          isset($freebusyPeriod[1]['day'])) {
          $content .= iCalUtilityFunctions::_format_date_time($freebusyPeriod[1]);
        }
        else {                                  // period=  -> dur-time
          $content .= iCalUtilityFunctions::_format_duration($freebusyPeriod[1]);
        }
        if ($fno < $cnt) {
          $content .= ',';
        }
        $fno++;
      }
      $output .= $this->_createElement('FREEBUSY', $attributes, $content);
    }

    return $output;
  }

  /**
   * set calendar component property freebusy
   *
   * @param string  $fbType
   * @param array   $fbValues
   * @param array   $params , optional
   * @param integer $index  , optional
   *
   * @return bool
   * @since  2.8.10 - 2011-03-24
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setFreebusy($fbType, $fbValues, $params = false, $index = false) {
    if (empty($fbValues)) {
      if ($this->getConfig('allowEmpty')) {
        iCalUtilityFunctions::_setMval($this->freebusy, null, $params, false, $index);

        return true;
      }
      else {
        return false;
      }
    }
    $fbType = strtoupper($fbType);
    if ((!in_array($fbType, array('FREE', 'BUSY', 'BUSY-UNAVAILABLE', 'BUSY-TENTATIVE'))) &&
      ('X-' != substr($fbType, 0, 2))) {
      $fbType = 'BUSY';
    }
    $input = array('fbtype' => $fbType);
    foreach ($fbValues as $fbPeriod) {   // periods => period
      if (empty($fbPeriod)) {
        continue;
      }
      $freebusyPeriod = array();
      foreach ($fbPeriod as $fbMember) { // pairs => singlepart
        $freebusyPairMember = array();
        if (is_array($fbMember)) {
          if (iCalUtilityFunctions::_isArrayDate($fbMember)) { // date-time value
            $freebusyPairMember       = iCalUtilityFunctions::_date_time_array($fbMember, 7);
            $freebusyPairMember['tz'] = 'Z';
          }
          elseif (iCalUtilityFunctions::_isArrayTimestampDate($fbMember)) { // timestamp value
            $freebusyPairMember       = iCalUtilityFunctions::_timestamp2date($fbMember['timestamp'], 7);
            $freebusyPairMember['tz'] = 'Z';
          }
          else {                                         // array format duration
            $freebusyPairMember = iCalUtilityFunctions::_duration_array($fbMember);
          }
        }
        elseif ((3 <= strlen(trim($fbMember))) &&    // string format duration
          (in_array($fbMember[0], array('P', '+', '-')))) {
          if ('P' != $fbMember[0]) {
            $fbmember = substr($fbMember, 1);
          }
          $freebusyPairMember = iCalUtilityFunctions::_duration_string($fbMember);
        }
        elseif (8 <= strlen(trim($fbMember))) { // text date ex. 2006-08-03 10:12:18
          $freebusyPairMember       = iCalUtilityFunctions::_date_time_string($fbMember, 7);
          $freebusyPairMember['tz'] = 'Z';
        }
        $freebusyPeriod[] = $freebusyPairMember;
      }
      $input[] = $freebusyPeriod;
    }
    iCalUtilityFunctions::_setMval($this->freebusy, $input, $params, false, $index);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: GEO
   */
  /**
   * creates formatted output for calendar component property geo
   *
   * @return string
   * @since  2.4.8 - 2008-10-21
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createGeo() {
    if (empty($this->geo)) {
      return false;
    }
    if (empty($this->geo['value'])) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('GEO') : false;
    }
    $attributes = $this->_createParams($this->geo['params']);
    $content    = null;
    $content    .= number_format((float)$this->geo['value']['latitude'], 6, '.', '');
    $content    .= ';';
    $content    .= number_format((float)$this->geo['value']['longitude'], 6, '.', '');

    return $this->_createElement('GEO', $attributes, $content);
  }

  /**
   * set calendar component property geo
   *
   * @param float $latitude
   * @param float $longitude
   * @param array $params optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.4.8 - 2008-11-04
   */
  function setGeo($latitude, $longitude, $params = false) {
    if (!empty($latitude) && !empty($longitude)) {
      if (!is_array($this->geo)) {
        $this->geo = array();
      }
      $this->geo['value']['latitude']  = $latitude;
      $this->geo['value']['longitude'] = $longitude;
      $this->geo['params']             = iCalUtilityFunctions::_setParams($params);
    }
    elseif ($this->getConfig('allowEmpty')) {
      $this->geo = array('value' => null, 'params' => iCalUtilityFunctions::_setParams($params));
    }
    else {
      return false;
    }

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: LAST-MODIFIED
   */
  /**
   * creates formatted output for calendar component property last-modified
   *
   * @return string
   * @since  2.4.8 - 2008-10-21
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createLastModified() {
    if (empty($this->lastmodified)) {
      return false;
    }
    $attributes = $this->_createParams($this->lastmodified['params']);
    $formatted  = iCalUtilityFunctions::_format_date_time($this->lastmodified['value'], 7);

    return $this->_createElement('LAST-MODIFIED', $attributes, $formatted);
  }

  /**
   * set calendar component property completed
   *
   * @param mixed $year   optional
   * @param mixed $month  optional
   * @param int   $day    optional
   * @param int   $hour   optional
   * @param int   $min    optional
   * @param int   $sec    optional
   * @param array $params optional
   *
   * @return boll
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.4.8 - 2008-10-23
   */
  function setLastModified($year = false, $month = false, $day = false, $hour = false, $min = false, $sec = false, $params = false) {
    if (empty($year)) {
      $year = date('Ymd\THis', mktime(date('H'), date('i'), date('s') - date('Z'), date('m'), date('d'), date('Y')));
    }
    $this->lastmodified = iCalUtilityFunctions::_setDate2($year, $month, $day, $hour, $min, $sec, $params);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: LOCATION
   */
  /**
   * creates formatted output for calendar component property location
   *
   * @return string
   * @since  2.4.8 - 2008-10-22
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createLocation() {
    if (empty($this->location)) {
      return false;
    }
    if (empty($this->location['value'])) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('LOCATION') : false;
    }
    $attributes = $this->_createParams($this->location['params'], array('ALTREP', 'LANGUAGE'));
    $content    = $this->_strrep($this->location['value']);

    return $this->_createElement('LOCATION', $attributes, $content);
  }

  /**
   * set calendar component property location
   * '
   *
   * @param string $value
   * @param array params optional
   *
   * @return bool
   * @since  2.4.8 - 2008-11-04
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setLocation($value, $params = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    $this->location = array('value' => $value, 'params' => iCalUtilityFunctions::_setParams($params));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: ORGANIZER
   */
  /**
   * creates formatted output for calendar component property organizer
   *
   * @return string
   * @since  2.6.33 - 2010-12-17
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createOrganizer() {
    if (empty($this->organizer)) {
      return false;
    }
    if (empty($this->organizer['value'])) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('ORGANIZER') : false;
    }
    $attributes = $this->_createParams($this->organizer['params']
      , array('CN', 'DIR', 'SENT-BY', 'LANGUAGE'));

    return $this->_createElement('ORGANIZER', $attributes, $this->organizer['value']);
  }

  /**
   * set calendar component property organizer
   *
   * @param string $value
   * @param array params optional
   *
   * @return bool
   * @since  2.6.27 - 2010-11-29
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setOrganizer($value, $params = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    if (false === ($pos = strpos(substr($value, 0, 9), ':'))) {
      $value = 'MAILTO:' . $value;
    }
    else {
      $value = strtolower(substr($value, 0, $pos)) . substr($value, $pos);
    }
    $value           = str_replace('mailto:', 'MAILTO:', $value);
    $this->organizer = array('value' => $value, 'params' => iCalUtilityFunctions::_setParams($params));
    if (isset($this->organizer['params']['SENT-BY'])) {
      if ('mailto:' !== strtolower(substr($this->organizer['params']['SENT-BY'], 0, 7))) {
        $this->organizer['params']['SENT-BY'] = 'MAILTO:' . $this->organizer['params']['SENT-BY'];
      }
      else {
        $this->organizer['params']['SENT-BY'] = 'MAILTO:' . substr($this->organizer['params']['SENT-BY'], 7);
      }
    }

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: PERCENT-COMPLETE
   */
  /**
   * creates formatted output for calendar component property percent-complete
   *
   * @return string
   * @since  2.9.3 - 2011-05-14
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createPercentComplete() {
    if (!isset($this->percentcomplete) || (empty($this->percentcomplete) && !is_numeric($this->percentcomplete))) {
      return false;
    }
    if (!isset($this->percentcomplete['value']) || (empty($this->percentcomplete['value']) && !is_numeric($this->percentcomplete['value']))) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('PERCENT-COMPLETE') : false;
    }
    $attributes = $this->_createParams($this->percentcomplete['params']);

    return $this->_createElement('PERCENT-COMPLETE', $attributes, $this->percentcomplete['value']);
  }

  /**
   * set calendar component property percent-complete
   *
   * @param int   $value
   * @param array $params optional
   *
   * @return bool
   * @since  2.9.3 - 2011-05-14
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setPercentComplete($value, $params = false) {
    if (empty($value) && !is_numeric($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    $this->percentcomplete = array('value' => $value, 'params' => iCalUtilityFunctions::_setParams($params));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: PRIORITY
   */
  /**
   * creates formatted output for calendar component property priority
   *
   * @return string
   * @since  2.9.3 - 2011-05-14
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createPriority() {
    if (!isset($this->priority) || (empty($this->priority) && !is_numeric($this->priority))) {
      return false;
    }
    if (!isset($this->priority['value']) || (empty($this->priority['value']) && !is_numeric($this->priority['value']))) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('PRIORITY') : false;
    }
    $attributes = $this->_createParams($this->priority['params']);

    return $this->_createElement('PRIORITY', $attributes, $this->priority['value']);
  }

  /**
   * set calendar component property priority
   *
   * @param int   $value
   * @param array $params optional
   *
   * @return bool
   * @since  2.9.3 - 2011-05-14
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setPriority($value, $params = false) {
    if (empty($value) && !is_numeric($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    $this->priority = array('value' => $value, 'params' => iCalUtilityFunctions::_setParams($params));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: RDATE
   */
  /**
   * creates formatted output for calendar component property rdate
   *
   * @return string
   * @since  2.4.16 - 2008-10-26
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createRdate() {
    if (empty($this->rdate)) {
      return false;
    }
    $utctime = (in_array($this->objName, array('vtimezone', 'standard', 'daylight'))) ? true : false;
    $output  = null;
    if ($utctime) {
      unset($this->rdate['params']['TZID']);
    }
    foreach ($this->rdate as $theRdate) {
      if (empty($theRdate['value'])) {
        if ($this->getConfig('allowEmpty')) {
          $output .= $this->_createElement('RDATE');
        }
        continue;
      }
      if ($utctime) {
        unset($theRdate['params']['TZID']);
      }
      $attributes = $this->_createParams($theRdate['params']);
      $cnt        = count($theRdate['value']);
      $content    = null;
      $rno        = 1;
      foreach ($theRdate['value'] as $rpix => $rdatePart) {
        $contentPart = null;
        if (is_array($rdatePart) &&
          isset($theRdate['params']['VALUE']) && ('PERIOD' == $theRdate['params']['VALUE'])) { // PERIOD
          if ($utctime) {
            unset($rdatePart[0]['tz']);
          }
          $formatted = iCalUtilityFunctions::_format_date_time($rdatePart[0]); // PERIOD part 1
          if ($utctime || !empty($theRdate['params']['TZID'])) {
            $formatted = str_replace('Z', '', $formatted);
          }
          if (0 < $rpix) {
            if (!empty($rdatePart[0]['tz']) && iCalUtilityFunctions::_isOffset($rdatePart[0]['tz'])) {
              if ('Z' != substr($formatted, -1)) {
                $formatted .= 'Z';
              }
            }
            else {
              $formatted = str_replace('Z', '', $formatted);
            }
          }
          $contentPart .= $formatted;
          $contentPart .= '/';
          $cnt2        = count($rdatePart[1]);
          if (array_key_exists('year', $rdatePart[1])) {
            if (array_key_exists('hour', $rdatePart[1])) {
              $cnt2 = 7;
            }                                      // date-time
            else {
              $cnt2 = 3;
            }                                      // date
          }
          elseif (array_key_exists('week', $rdatePart[1]))  // duration
          {
            $cnt2 = 5;
          }
          if ((7 == $cnt2) &&    // period=  -> date-time
            isset($rdatePart[1]['year']) &&
            isset($rdatePart[1]['month']) &&
            isset($rdatePart[1]['day'])) {
            if ($utctime) {
              unset($rdatePart[1]['tz']);
            }
            $formatted = iCalUtilityFunctions::_format_date_time($rdatePart[1]); // PERIOD part 2
            if ($utctime || !empty($theRdate['params']['TZID'])) {
              $formatted = str_replace('Z', '', $formatted);
            }
            if (!empty($rdatePart[0]['tz']) && iCalUtilityFunctions::_isOffset($rdatePart[0]['tz'])) {
              if ('Z' != substr($formatted, -1)) {
                $formatted .= 'Z';
              }
            }
            else {
              $formatted = str_replace('Z', '', $formatted);
            }
            $contentPart .= $formatted;
          }
          else {                                  // period=  -> dur-time
            $contentPart .= iCalUtilityFunctions::_format_duration($rdatePart[1]);
          }
        } // PERIOD end
        else { // SINGLE date start
          if ($utctime) {
            unset($rdatePart['tz']);
          }
          $formatted = iCalUtilityFunctions::_format_date_time($rdatePart);
          if ($utctime || !empty($theRdate['params']['TZID'])) {
            $formatted = str_replace('Z', '', $formatted);
          }
          if (!$utctime && (0 < $rpix)) {
            if (!empty($theRdate['value'][0]['tz']) && iCalUtilityFunctions::_isOffset($theRdate['value'][0]['tz'])) {
              if ('Z' != substr($formatted, -1)) {
                $formatted .= 'Z';
              }
            }
            else {
              $formatted = str_replace('Z', '', $formatted);
            }
          }
          $contentPart .= $formatted;
        }
        $content .= $contentPart;
        if ($rno < $cnt) {
          $content .= ',';
        }
        $rno++;
      }
      $output .= $this->_createElement('RDATE', $attributes, $content);
    }

    return $output;
  }

  /**
   * set calendar component property rdate
   *
   * @param array   $rdates
   * @param array   $params , optional
   * @param integer $index  , optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.5.1 - 2008-11-07
   */
  function setRdate($rdates, $params = false, $index = false) {
    if (empty($rdates)) {
      if ($this->getConfig('allowEmpty')) {
        iCalUtilityFunctions::_setMval($this->rdate, null, $params, false, $index);

        return true;
      }
      else {
        return false;
      }
    }
    $input = array('params' => iCalUtilityFunctions::_setParams($params, array('VALUE' => 'DATE-TIME')));
    if (in_array($this->objName, array('vtimezone', 'standard', 'daylight'))) {
      unset($input['params']['TZID']);
      $input['params']['VALUE'] = 'DATE-TIME';
    }
    /*  check if PERIOD, if not set */
    if ((!isset($input['params']['VALUE']) || !in_array($input['params']['VALUE'], array('DATE', 'PERIOD'))) &&
      isset($rdates[0]) && is_array($rdates[0]) && (2 == count($rdates[0])) &&
      isset($rdates[0][0]) && isset($rdates[0][1]) && !isset($rdates[0]['timestamp']) &&
      ((is_array($rdates[0][0]) && (isset($rdates[0][0]['timestamp']) ||
            iCalUtilityFunctions::_isArrayDate($rdates[0][0]))) ||
        (is_string($rdates[0][0]) && (8 <= strlen(trim($rdates[0][0]))))) &&
      (is_array($rdates[0][1]) || (is_string($rdates[0][1]) && (3 <= strlen(trim($rdates[0][1])))))) {
      $input['params']['VALUE'] = 'PERIOD';
    }
    /* check 1:st date, upd. $parno (opt) and save ev. timezone **/
    $date = reset($rdates);
    if (isset($input['params']['VALUE']) && ('PERIOD' == $input['params']['VALUE'])) // PERIOD
    {
      $date = reset($date);
    }
    iCalUtilityFunctions::_chkdatecfg($date, $parno, $input['params']);
    if (in_array($this->objName, array('vtimezone', 'standard', 'daylight'))) {
      unset($input['params']['TZID']);
    }
    iCalUtilityFunctions::_existRem($input['params'], 'VALUE', 'DATE-TIME'); // remove default
    foreach ($rdates as $rpix => $theRdate) {
      $inputa = null;
      if (is_array($theRdate)) {
        if (isset($input['params']['VALUE']) && ('PERIOD' == $input['params']['VALUE'])) { // PERIOD
          foreach ($theRdate as $rix => $rPeriod) {
            if (is_array($rPeriod)) {
              if (iCalUtilityFunctions::_isArrayTimestampDate($rPeriod))      // timestamp
              {
                $inputab = (isset($rPeriod['tz'])) ? iCalUtilityFunctions::_timestamp2date($rPeriod, $parno) : iCalUtilityFunctions::_timestamp2date($rPeriod, 6);
              }
              elseif (iCalUtilityFunctions::_isArrayDate($rPeriod)) {
                $inputab = (3 < count($rPeriod)) ? iCalUtilityFunctions::_date_time_array($rPeriod, $parno) : iCalUtilityFunctions::_date_time_array($rPeriod, 6);
              }
              elseif ((1 == count($rPeriod)) && (8 <= strlen(reset($rPeriod))))  // text-date
              {
                $inputab = iCalUtilityFunctions::_date_time_string(reset($rPeriod), $parno);
              }
              else                                               // array format duration
              {
                $inputab = iCalUtilityFunctions::_duration_array($rPeriod);
              }
            }
            elseif ((3 <= strlen(trim($rPeriod))) &&          // string format duration
              (in_array($rPeriod[0], array('P', '+', '-')))) {
              if ('P' != $rPeriod[0]) {
                $rPeriod = substr($rPeriod, 1);
              }
              $inputab = iCalUtilityFunctions::_duration_string($rPeriod);
            }
            elseif (8 <= strlen(trim($rPeriod)))              // text date ex. 2006-08-03 10:12:18
            {
              $inputab = iCalUtilityFunctions::_date_time_string($rPeriod, $parno);
            }
            if (isset($input['params']['TZID']) ||
              (isset($inputab['tz']) && !iCalUtilityFunctions::_isOffset($inputab['tz'])) ||
              (isset($inputa[0]) && (!isset($inputa[0]['tz']))) ||
              (isset($inputa[0]['tz']) && !iCalUtilityFunctions::_isOffset($inputa[0]['tz']))) {
              unset($inputab['tz']);
            }
            $inputa[] = $inputab;
          }
        } // PERIOD end
        elseif (iCalUtilityFunctions::_isArrayTimestampDate($theRdate))      // timestamp
        {
          $inputa = iCalUtilityFunctions::_timestamp2date($theRdate, $parno);
        }
        else                                                                    // date[-time]
        {
          $inputa = iCalUtilityFunctions::_date_time_array($theRdate, $parno);
        }
      }
      elseif (8 <= strlen(trim($theRdate)))                   // text date ex. 2006-08-03 10:12:18
      {
        $inputa = iCalUtilityFunctions::_date_time_string($theRdate, $parno);
      }
      if (!isset($input['params']['VALUE']) || ('PERIOD' != $input['params']['VALUE'])) { // no PERIOD
        if (3 == $parno) {
          unset($inputa['hour'], $inputa['min'], $inputa['sec'], $inputa['tz']);
        }
        elseif (isset($inputa['tz'])) {
          $inputa['tz'] = (string)$inputa['tz'];
        }
        if (isset($input['params']['TZID']) ||
          (isset($inputa['tz']) && !iCalUtilityFunctions::_isOffset($inputa['tz'])) ||
          (isset($input['value'][0]) && (!isset($input['value'][0]['tz']))) ||
          (isset($input['value'][0]['tz']) && !iCalUtilityFunctions::_isOffset($input['value'][0]['tz']))) {
          unset($inputa['tz']);
        }
      }
      $input['value'][] = $inputa;
    }
    if (3 == $parno) {
      $input['params']['VALUE'] = 'DATE';
      unset($input['params']['TZID']);
    }
    iCalUtilityFunctions::_setMval($this->rdate, $input['value'], $input['params'], false, $index);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: RECURRENCE-ID
   */
  /**
   * creates formatted output for calendar component property recurrence-id
   *
   * @return string
   * @since  2.9.6 - 2011-05-15
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createRecurrenceid() {
    if (empty($this->recurrenceid)) {
      return false;
    }
    if (empty($this->recurrenceid['value'])) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('RECURRENCE-ID') : false;
    }
    $formatted = iCalUtilityFunctions::_format_date_time($this->recurrenceid['value']);
    if ((false !== ($tzid = $this->getConfig('TZID'))) &&
      (!isset($this->recurrenceid['params']['VALUE']) || ($this->recurrenceid['params']['VALUE'] != 'DATE')) &&
      !isset($this->recurrenceid['params']['TZID'])) {
      $this->recurrenceid['params']['TZID'] = $tzid;
    }
    $attributes = $this->_createParams($this->recurrenceid['params']);

    return $this->_createElement('RECURRENCE-ID', $attributes, $formatted);
  }

  /**
   * set calendar component property recurrence-id
   *
   * @param mixed $year
   * @param mixed $month  optional
   * @param int   $day    optional
   * @param int   $hour   optional
   * @param int   $min    optional
   * @param int   $sec    optional
   * @param array $params optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.9.6 - 2011-05-15
   */
  function setRecurrenceid($year, $month = false, $day = false, $hour = false, $min = false, $sec = false, $tz = false, $params = false) {
    if (empty($year)) {
      if ($this->getConfig('allowEmpty')) {
        $this->recurrenceid = array('value' => null, 'params' => null);

        return true;
      }
      else {
        return false;
      }
    }
    $this->recurrenceid = iCalUtilityFunctions::_setDate($year, $month, $day, $hour, $min, $sec, $tz, $params, null, null, $this->getConfig('TZID'));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: RELATED-TO
   */
  /**
   * creates formatted output for calendar component property related-to
   *
   * @return string
   * @since  2.4.8 - 2008-10-23
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createRelatedTo() {
    if (empty($this->relatedto)) {
      return false;
    }
    $output = null;
    foreach ($this->relatedto as $relation) {
      if (empty($relation['value'])) {
        if ($this->getConfig('allowEmpty')) {
          $output .= $this->_createElement('RELATED-TO', $this->_createParams($relation['params']));
        }
        continue;
      }
      $attributes = $this->_createParams($relation['params']);
      $content    = ('xcal' != $this->format) ? '<' : '';
      $content    .= $this->_strrep($relation['value']);
      $content    .= ('xcal' != $this->format) ? '>' : '';
      $output     .= $this->_createElement('RELATED-TO', $attributes, $content);
    }

    return $output;
  }

  /**
   * set calendar component property related-to
   *
   * @param float $relid
   * @param array $params , optional
   * @param index $index  , optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.5.1 - 2008-11-07
   */
  function setRelatedTo($value, $params = false, $index = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    if (('<' == substr($value, 0, 1)) && ('>' == substr($value, -1))) {
      $value = substr($value, 1, (strlen($value) - 2));
    }
    iCalUtilityFunctions::_existRem($params, 'RELTYPE', 'PARENT', true); // remove default
    iCalUtilityFunctions::_setMval($this->relatedto, $value, $params, false, $index);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: REPEAT
   */
  /**
   * creates formatted output for calendar component property repeat
   *
   * @return string
   * @since  2.9.3 - 2011-05-14
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createRepeat() {
    if (!isset($this->repeat) || (empty($this->repeat) && !is_numeric($this->repeat))) {
      return false;
    }
    if (!isset($this->repeat['value']) || (empty($this->repeat['value']) && !is_numeric($this->repeat['value']))) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('REPEAT') : false;
    }
    $attributes = $this->_createParams($this->repeat['params']);

    return $this->_createElement('REPEAT', $attributes, $this->repeat['value']);
  }

  /**
   * set calendar component property transp
   *
   * @param string $value
   * @param array  $params optional
   *
   * @return void
   * @since  2.9.3 - 2011-05-14
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setRepeat($value, $params = false) {
    if (empty($value) && !is_numeric($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    $this->repeat = array('value' => $value, 'params' => iCalUtilityFunctions::_setParams($params));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: REQUEST-STATUS
   */
  /**
   * creates formatted output for calendar component property request-status
   * @return string
   * @since  2.4.8 - 2008-10-23
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createRequestStatus() {
    if (empty($this->requeststatus)) {
      return false;
    }
    $output = null;
    foreach ($this->requeststatus as $rstat) {
      if (empty($rstat['value']['statcode'])) {
        if ($this->getConfig('allowEmpty')) {
          $output .= $this->_createElement('REQUEST-STATUS');
        }
        continue;
      }
      $attributes = $this->_createParams($rstat['params'], array('LANGUAGE'));
      $content    = number_format((float)$rstat['value']['statcode'], 2, '.', '');
      $content    .= ';' . $this->_strrep($rstat['value']['text']);
      if (isset($rstat['value']['extdata'])) {
        $content .= ';' . $this->_strrep($rstat['value']['extdata']);
      }
      $output .= $this->_createElement('REQUEST-STATUS', $attributes, $content);
    }

    return $output;
  }

  /**
   * set calendar component property request-status
   *
   * @param float   $statcode
   * @param string  $text
   * @param string  $extdata , optional
   * @param array   $params  , optional
   * @param integer $index   , optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.5.1 - 2008-11-05
   */
  function setRequestStatus($statcode, $text, $extdata = false, $params = false, $index = false) {
    if (empty($statcode) || empty($text)) {
      if ($this->getConfig('allowEmpty')) {
        $statcode = $text = null;
      }
      else {
        return false;
      }
    }
    $input = array('statcode' => $statcode, 'text' => $text);
    if ($extdata) {
      $input['extdata'] = $extdata;
    }
    iCalUtilityFunctions::_setMval($this->requeststatus, $input, $params, false, $index);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: RESOURCES
   */
  /**
   * creates formatted output for calendar component property resources
   *
   * @return string
   * @since  2.4.8 - 2008-10-23
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createResources() {
    if (empty($this->resources)) {
      return false;
    }
    $output = null;
    foreach ($this->resources as $resource) {
      if (empty($resource['value'])) {
        if ($this->getConfig('allowEmpty')) {
          $output .= $this->_createElement('RESOURCES');
        }
        continue;
      }
      $attributes = $this->_createParams($resource['params'], array('ALTREP', 'LANGUAGE'));
      if (is_array($resource['value'])) {
        foreach ($resource['value'] as $rix => $resourcePart) {
          $resource['value'][$rix] = $this->_strrep($resourcePart);
        }
        $content = implode(',', $resource['value']);
      }
      else {
        $content = $this->_strrep($resource['value']);
      }
      $output .= $this->_createElement('RESOURCES', $attributes, $content);
    }

    return $output;
  }

  /**
   * set calendar component property recources
   *
   * @param mixed   $value
   * @param array   $params , optional
   * @param integer $index  , optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.5.1 - 2008-11-05
   */
  function setResources($value, $params = false, $index = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    iCalUtilityFunctions::_setMval($this->resources, $value, $params, false, $index);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: RRULE
   */
  /**
   * creates formatted output for calendar component property rrule
   *
   * @return string
   * @since  2.4.8 - 2008-10-21
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createRrule() {
    if (empty($this->rrule)) {
      return false;
    }

    return $this->_format_recur('RRULE', $this->rrule);
  }

  /**
   * set calendar component property rrule
   *
   * @param array   $rruleset
   * @param array   $params , optional
   * @param integer $index  , optional
   *
   * @return void
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.5.1 - 2008-11-05
   */
  function setRrule($rruleset, $params = false, $index = false) {
    if (empty($rruleset)) {
      if ($this->getConfig('allowEmpty')) {
        $rruleset = null;
      }
      else {
        return false;
      }
    }
    iCalUtilityFunctions::_setMval($this->rrule, iCalUtilityFunctions::_setRexrule($rruleset), $params, false, $index);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: SEQUENCE
   */
  /**
   * creates formatted output for calendar component property sequence
   * @return string
   * @since  2.9.3 - 2011-05-14
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createSequence() {
    if (!isset($this->sequence) || (empty($this->sequence) && !is_numeric($this->sequence))) {
      return false;
    }
    if ((!isset($this->sequence['value']) || (empty($this->sequence['value']) && !is_numeric($this->sequence['value']))) &&
      ('0' != $this->sequence['value'])) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('SEQUENCE') : false;
    }
    $attributes = $this->_createParams($this->sequence['params']);

    return $this->_createElement('SEQUENCE', $attributes, $this->sequence['value']);
  }

  /**
   * set calendar component property sequence
   *
   * @param int   $value  optional
   * @param array $params optional
   *
   * @return bool
   * @since  2.10.8 - 2011-09-19
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setSequence($value = false, $params = false) {
    if ((empty($value) && !is_numeric($value)) && ('0' != $value)) {
      $value = (isset($this->sequence['value']) && (-1 < $this->sequence['value'])) ? $this->sequence['value'] + 1 : '0';
    }
    $this->sequence = array('value' => $value, 'params' => iCalUtilityFunctions::_setParams($params));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: STATUS
   */
  /**
   * creates formatted output for calendar component property status
   *
   * @return string
   * @since  2.4.8 - 2008-10-21
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createStatus() {
    if (empty($this->status)) {
      return false;
    }
    if (empty($this->status['value'])) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('STATUS') : false;
    }
    $attributes = $this->_createParams($this->status['params']);

    return $this->_createElement('STATUS', $attributes, $this->status['value']);
  }

  /**
   * set calendar component property status
   *
   * @param string $value
   * @param array  $params optional
   *
   * @return bool
   * @since  2.4.8 - 2008-11-04
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setStatus($value, $params = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    $this->status = array('value' => $value, 'params' => iCalUtilityFunctions::_setParams($params));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: SUMMARY
   */
  /**
   * creates formatted output for calendar component property summary
   *
   * @return string
   * @since  2.4.8 - 2008-10-21
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createSummary() {
    if (empty($this->summary)) {
      return false;
    }
    if (empty($this->summary['value'])) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('SUMMARY') : false;
    }
    $attributes = $this->_createParams($this->summary['params'], array('ALTREP', 'LANGUAGE'));
    $content    = $this->_strrep($this->summary['value']);

    return $this->_createElement('SUMMARY', $attributes, $content);
  }

  /**
   * set calendar component property summary
   *
   * @param string $value
   * @param string $params optional
   *
   * @return bool
   * @since  2.4.8 - 2008-11-04
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setSummary($value, $params = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    $this->summary = array('value' => $value, 'params' => iCalUtilityFunctions::_setParams($params));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: TRANSP
   */
  /**
   * creates formatted output for calendar component property transp
   *
   * @return string
   * @since  2.4.8 - 2008-10-21
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createTransp() {
    if (empty($this->transp)) {
      return false;
    }
    if (empty($this->transp['value'])) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('TRANSP') : false;
    }
    $attributes = $this->_createParams($this->transp['params']);

    return $this->_createElement('TRANSP', $attributes, $this->transp['value']);
  }

  /**
   * set calendar component property transp
   *
   * @param string $value
   * @param string $params optional
   *
   * @return bool
   * @since  2.4.8 - 2008-11-04
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setTransp($value, $params = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    $this->transp = array('value' => $value, 'params' => iCalUtilityFunctions::_setParams($params));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: TRIGGER
   */
  /**
   * creates formatted output for calendar component property trigger
   *
   * @return string
   * @since  2.4.16 - 2008-10-21
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createTrigger() {
    if (empty($this->trigger)) {
      return false;
    }
    if (empty($this->trigger['value'])) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('TRIGGER') : false;
    }
    $content = $attributes = null;
    if (isset($this->trigger['value']['year']) &&
      isset($this->trigger['value']['month']) &&
      isset($this->trigger['value']['day'])) {
      $content .= iCalUtilityFunctions::_format_date_time($this->trigger['value']);
    }
    else {
      if (true !== $this->trigger['value']['relatedStart']) {
        $attributes .= $this->intAttrDelimiter . 'RELATED=END';
      }
      if ($this->trigger['value']['before']) {
        $content .= '-';
      }
      $content .= iCalUtilityFunctions::_format_duration($this->trigger['value']);
    }
    $attributes .= $this->_createParams($this->trigger['params']);

    return $this->_createElement('TRIGGER', $attributes, $content);
  }

  /**
   * set calendar component property trigger
   *
   * @param mixed $year
   * @param mixed $month        optional
   * @param int   $day          optional
   * @param int   $week         optional
   * @param int   $hour         optional
   * @param int   $min          optional
   * @param int   $sec          optional
   * @param bool  $relatedStart optional
   * @param bool  $before       optional
   * @param array $params       optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.9.9 - 2011-06-17
   */
  function setTrigger($year, $month = null, $day = null, $week = false, $hour = false, $min = false, $sec = false, $relatedStart = true, $before = true, $params = false) {
    if (empty($year) && empty($month) && empty($day) && empty($week) && empty($hour) && empty($min) && empty($sec)) {
      if ($this->getConfig('allowEmpty')) {
        $this->trigger = array('value' => null, 'params' => iCalUtilityFunctions::_setParams($params));

        return true;
      }
      else {
        return false;
      }
    }
    if (iCalUtilityFunctions::_isArrayTimestampDate($year)) { // timestamp
      $params = iCalUtilityFunctions::_setParams($month);
      $date   = iCalUtilityFunctions::_timestamp2date($year, 7);
      foreach ($date as $k => $v) {
        $$k = $v;
      }
    }
    elseif (is_array($year) && (is_array($month) || empty($month))) {
      $params = iCalUtilityFunctions::_setParams($month);
      if (!(array_key_exists('year', $year) &&   // exclude date-time
        array_key_exists('month', $year) &&
        array_key_exists('day', $year))) {  // when this must be a duration
        if (isset($params['RELATED']) && ('END' == strtoupper($params['RELATED']))) {
          $relatedStart = false;
        }
        else {
          $relatedStart = (array_key_exists('relatedStart', $year) && (true !== $year['relatedStart'])) ? false : true;
        }
        $before = (array_key_exists('before', $year) && (true !== $year['before'])) ? false : true;
      }
      $SSYY  = (array_key_exists('year', $year)) ? $year['year'] : null;
      $month = (array_key_exists('month', $year)) ? $year['month'] : null;
      $day   = (array_key_exists('day', $year)) ? $year['day'] : null;
      $week  = (array_key_exists('week', $year)) ? $year['week'] : null;
      $hour  = (array_key_exists('hour', $year)) ? $year['hour'] : 0; //null;
      $min   = (array_key_exists('min', $year)) ? $year['min'] : 0; //null;
      $sec   = (array_key_exists('sec', $year)) ? $year['sec'] : 0; //null;
      $year  = $SSYY;
    }
    elseif (is_string($year) && (is_array($month) || empty($month))) {  // duration or date in a string
      $params = iCalUtilityFunctions::_setParams($month);
      if (in_array($year[0], array('P', '+', '-'))) { // duration
        $relatedStart = (isset($params['RELATED']) && ('END' == strtoupper($params['RELATED']))) ? false : true;
        $before       = ('-' == $year[0]) ? true : false;
        if ('P' != $year[0]) {
          $year = substr($year, 1);
        }
        $date = iCalUtilityFunctions::_duration_string($year);
      }
      else   // date
      {
        $date = iCalUtilityFunctions::_date_time_string($year, 7);
      }
      unset($year, $month, $day);
      if (empty($date)) {
        $sec = 0;
      }
      else {
        foreach ($date as $k => $v) {
          $$k = $v;
        }
      }
    }
    else // single values in function input parameters
    {
      $params = iCalUtilityFunctions::_setParams($params);
    }
    if (!empty($year) && !empty($month) && !empty($day)) { // date
      $params['VALUE']        = 'DATE-TIME';
      $hour                   = ($hour) ? $hour : 0;
      $min                    = ($min) ? $min : 0;
      $sec                    = ($sec) ? $sec : 0;
      $this->trigger          = array('params' => $params);
      $this->trigger['value'] = array('year' => $year
      , 'month'                              => $month
      , 'day'                                => $day
      , 'hour'                               => $hour
      , 'min'                                => $min
      , 'sec'                                => $sec
      , 'tz'                                 => 'Z');

      return true;
    }
    elseif ((empty($year) && empty($month)) &&    // duration
      ((!empty($week) || (0 == $week)) ||
        (!empty($day) || (0 == $day)) ||
        (!empty($hour) || (0 == $hour)) ||
        (!empty($min) || (0 == $min)) ||
        (!empty($sec) || (0 == $sec)))) {
      unset($params['RELATED']); // set at output creation (END only)
      unset($params['VALUE']);   // 'DURATION' default
      $this->trigger          = array('params' => $params);
      $this->trigger['value'] = array();
      if (!empty($week)) {
        $this->trigger['value']['week'] = $week;
      }
      if (!empty($day)) {
        $this->trigger['value']['day'] = $day;
      }
      if (!empty($hour)) {
        $this->trigger['value']['hour'] = $hour;
      }
      if (!empty($min)) {
        $this->trigger['value']['min'] = $min;
      }
      if (!empty($sec)) {
        $this->trigger['value']['sec'] = $sec;
      }
      if (empty($this->trigger['value'])) {
        $this->trigger['value']['sec'] = 0;
        $before                        = false;
      }
      $relatedStart                           = (false !== $relatedStart) ? true : false;
      $before                                 = (false !== $before) ? true : false;
      $this->trigger['value']['relatedStart'] = $relatedStart;
      $this->trigger['value']['before']       = $before;

      return true;
    }

    return false;
  }
  /*********************************************************************************/
  /**
   * Property Name: TZID
   */
  /**
   * creates formatted output for calendar component property tzid
   *
   * @return string
   * @since  2.4.8 - 2008-10-21
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createTzid() {
    if (empty($this->tzid)) {
      return false;
    }
    if (empty($this->tzid['value'])) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('TZID') : false;
    }
    $attributes = $this->_createParams($this->tzid['params']);

    return $this->_createElement('TZID', $attributes, $this->_strrep($this->tzid['value']));
  }

  /**
   * set calendar component property tzid
   *
   * @param string $value
   * @param array  $params optional
   *
   * @return bool
   * @since  2.4.8 - 2008-11-04
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setTzid($value, $params = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    $this->tzid = array('value' => $value, 'params' => iCalUtilityFunctions::_setParams($params));

    return true;
  }
  /*********************************************************************************/
  /**
   * .. .
   * Property Name: TZNAME
   */
  /**
   * creates formatted output for calendar component property tzname
   *
   * @return string
   * @since  2.4.8 - 2008-10-21
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createTzname() {
    if (empty($this->tzname)) {
      return false;
    }
    $output = null;
    foreach ($this->tzname as $theName) {
      if (!empty($theName['value'])) {
        $attributes = $this->_createParams($theName['params'], array('LANGUAGE'));
        $output     .= $this->_createElement('TZNAME', $attributes, $this->_strrep($theName['value']));
      }
      elseif ($this->getConfig('allowEmpty')) {
        $output .= $this->_createElement('TZNAME');
      }
    }

    return $output;
  }

  /**
   * set calendar component property tzname
   *
   * @param string  $value
   * @param string  $params , optional
   * @param integer $index  , optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.5.1 - 2008-11-05
   */
  function setTzname($value, $params = false, $index = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    iCalUtilityFunctions::_setMval($this->tzname, $value, $params, false, $index);

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: TZOFFSETFROM
   */
  /**
   * creates formatted output for calendar component property tzoffsetfrom
   *
   * @return string
   * @since  2.4.8 - 2008-10-21
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createTzoffsetfrom() {
    if (empty($this->tzoffsetfrom)) {
      return false;
    }
    if (empty($this->tzoffsetfrom['value'])) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('TZOFFSETFROM') : false;
    }
    $attributes = $this->_createParams($this->tzoffsetfrom['params']);

    return $this->_createElement('TZOFFSETFROM', $attributes, $this->tzoffsetfrom['value']);
  }

  /**
   * set calendar component property tzoffsetfrom
   *
   * @param string $value
   * @param string $params optional
   *
   * @return bool
   * @since  2.4.8 - 2008-11-04
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setTzoffsetfrom($value, $params = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    $this->tzoffsetfrom = array('value' => $value, 'params' => iCalUtilityFunctions::_setParams($params));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: TZOFFSETTO
   */
  /**
   * creates formatted output for calendar component property tzoffsetto
   *
   * @return string
   * @since  2.4.8 - 2008-10-21
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createTzoffsetto() {
    if (empty($this->tzoffsetto)) {
      return false;
    }
    if (empty($this->tzoffsetto['value'])) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('TZOFFSETTO') : false;
    }
    $attributes = $this->_createParams($this->tzoffsetto['params']);

    return $this->_createElement('TZOFFSETTO', $attributes, $this->tzoffsetto['value']);
  }

  /**
   * set calendar component property tzoffsetto
   *
   * @param string $value
   * @param string $params optional
   *
   * @return bool
   * @since  2.4.8 - 2008-11-04
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setTzoffsetto($value, $params = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    $this->tzoffsetto = array('value' => $value, 'params' => iCalUtilityFunctions::_setParams($params));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: TZURL
   */
  /**
   * creates formatted output for calendar component property tzurl
   *
   * @return string
   * @since  2.4.8 - 2008-10-21
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createTzurl() {
    if (empty($this->tzurl)) {
      return false;
    }
    if (empty($this->tzurl['value'])) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('TZURL') : false;
    }
    $attributes = $this->_createParams($this->tzurl['params']);

    return $this->_createElement('TZURL', $attributes, $this->tzurl['value']);
  }

  /**
   * set calendar component property tzurl
   *
   * @param string $value
   * @param string $params optional
   *
   * @return boll
   * @since  2.4.8 - 2008-11-04
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setTzurl($value, $params = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    $this->tzurl = array('value' => $value, 'params' => iCalUtilityFunctions::_setParams($params));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: UID
   */
  /**
   * creates formatted output for calendar component property uid
   *
   * @return string
   * @since  0.9.7 - 2006-11-20
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createUid() {
    if (0 >= count($this->uid)) {
      $this->_makeuid();
    }
    $attributes = $this->_createParams($this->uid['params']);

    return $this->_createElement('UID', $attributes, $this->uid['value']);
  }

  /**
   * create an unique id for this calendar component object instance
   *
   * @return void
   * @since  2.2.7 - 2007-09-04
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function _makeUid() {
    $date   = date('Ymd\THisT');
    $unique = substr(microtime(), 2, 4);
    $base   = 'aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPrRsStTuUvVxXuUvVwWzZ1234567890';
    $start  = 0;
    $end    = strlen($base) - 1;
    $length = 6;
    $str    = null;
    for ($p = 0; $p < $length; $p++) {
      $unique .= $base[mt_rand($start, $end)];
    }
    $this->uid          = array('params' => null);
    $this->uid['value'] = $date . '-' . $unique . '@' . $this->getConfig('unique_id');
  }

  /**
   * set calendar component property uid
   *
   * @param string $value
   * @param string $params optional
   *
   * @return bool
   * @since  2.4.8 - 2008-11-04
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setUid($value, $params = false) {
    if (empty($value)) {
      return false;
    } // no allowEmpty check here !!!!
    $this->uid = array('value' => $value, 'params' => iCalUtilityFunctions::_setParams($params));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: URL
   */
  /**
   * creates formatted output for calendar component property url
   *
   * @return string
   * @since  2.4.8 - 2008-10-21
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createUrl() {
    if (empty($this->url)) {
      return false;
    }
    if (empty($this->url['value'])) {
      return ($this->getConfig('allowEmpty')) ? $this->_createElement('URL') : false;
    }
    $attributes = $this->_createParams($this->url['params']);

    return $this->_createElement('URL', $attributes, $this->url['value']);
  }

  /**
   * set calendar component property url
   *
   * @param string $value
   * @param string $params optional
   *
   * @return bool
   * @since  2.4.8 - 2008-11-04
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function setUrl($value, $params = false) {
    if (empty($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    $this->url = array('value' => $value, 'params' => iCalUtilityFunctions::_setParams($params));

    return true;
  }
  /*********************************************************************************/
  /**
   * Property Name: x-prop
   */
  /**
   * creates formatted output for calendar component property x-prop
   *
   * @return string
   * @since  2.9.3 - 2011-05-14
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createXprop() {
    if (empty($this->xprop)) {
      return false;
    }
    $output = null;
    foreach ($this->xprop as $label => $xpropPart) {
      if (!isset($xpropPart['value']) || (empty($xpropPart['value']) && !is_numeric($xpropPart['value']))) {
        if ($this->getConfig('allowEmpty')) {
          $output .= $this->_createElement($label);
        }
        continue;
      }
      $attributes = $this->_createParams($xpropPart['params'], array('LANGUAGE'));
      if (is_array($xpropPart['value'])) {
        foreach ($xpropPart['value'] as $pix => $theXpart) {
          $xpropPart['value'][$pix] = $this->_strrep($theXpart);
        }
        $xpropPart['value'] = implode(',', $xpropPart['value']);
      }
      else {
        $xpropPart['value'] = $this->_strrep($xpropPart['value']);
      }
      $output .= $this->_createElement($label, $attributes, $xpropPart['value']);
    }

    return $output;
  }

  /**
   * set calendar component property x-prop
   *
   * @param string $label
   * @param mixed  $value
   * @param array  $params optional
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.9.3 - 2011-05-14
   */
  function setXprop($label, $value, $params = false) {
    if (empty($label)) {
      return;
    }
    if (empty($value) && !is_numeric($value)) {
      if ($this->getConfig('allowEmpty')) {
        $value = null;
      }
      else {
        return false;
      }
    }
    $xprop           = array('value' => $value);
    $xprop['params'] = iCalUtilityFunctions::_setParams($params);
    if (!is_array($this->xprop)) {
      $this->xprop = array();
    }
    $this->xprop[strtoupper($label)] = $xprop;

    return true;
  }
  /*********************************************************************************/
  /*********************************************************************************/
  /**
   * create element format parts
   *
   * @return string
   * @since  2.0.6 - 2006-06-20
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function _createFormat() {
    $objectname = null;
    switch ($this->format) {
      case 'xcal':
        $objectname               = (isset($this->timezonetype)) ?
          strtolower($this->timezonetype) : strtolower($this->objName);
        $this->componentStart1    = $this->elementStart1 = '<';
        $this->componentStart2    = $this->elementStart2 = '>';
        $this->componentEnd1      = $this->elementEnd1 = '</';
        $this->componentEnd2      = $this->elementEnd2 = '>' . $this->nl;
        $this->intAttrDelimiter   = '<!-- -->';
        $this->attributeDelimiter = $this->nl;
        $this->valueInit          = null;
        break;
      default:
        $objectname               = (isset($this->timezonetype)) ?
          strtoupper($this->timezonetype) : strtoupper($this->objName);
        $this->componentStart1    = 'BEGIN:';
        $this->componentStart2    = null;
        $this->componentEnd1      = 'END:';
        $this->componentEnd2      = $this->nl;
        $this->elementStart1      = null;
        $this->elementStart2      = null;
        $this->elementEnd1        = null;
        $this->elementEnd2        = $this->nl;
        $this->intAttrDelimiter   = '<!-- -->';
        $this->attributeDelimiter = ';';
        $this->valueInit          = ':';
        break;
    }

    return $objectname;
  }

  /**
   * creates formatted output for calendar component property
   *
   * @param string $label      property name
   * @param string $attributes property attributes
   * @param string $content    property content (optional)
   *
   * @return string
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.6.22 - 2010-12-06
   */
  function _createElement($label, $attributes = null, $content = false) {
    switch ($this->format) {
      case 'xcal':
        $label = strtolower($label);
        break;
      default:
        $label = strtoupper($label);
        break;
    }
    $output             = $this->elementStart1 . $label;
    $categoriesAttrLang = null;
    $attachInlineBinary = false;
    $attachfmttype      = null;
    if (!empty($attributes)) {
      $attributes = trim($attributes);
      if ('xcal' == $this->format) {
        $attributes2 = explode($this->intAttrDelimiter, $attributes);
        $attributes  = null;
        foreach ($attributes2 as $attribute) {
          $attrKVarr = explode('=', $attribute);
          if (empty($attrKVarr[0])) {
            continue;
          }
          if (!isset($attrKVarr[1])) {
            $attrValue = $attrKVarr[0];
            $attrKey   = null;
          }
          elseif (2 == count($attrKVarr)) {
            $attrKey   = strtolower($attrKVarr[0]);
            $attrValue = $attrKVarr[1];
          }
          else {
            $attrKey = strtolower($attrKVarr[0]);
            unset($attrKVarr[0]);
            $attrValue = implode('=', $attrKVarr);
          }
          if (('attach' == $label) && (in_array($attrKey, array('fmttype', 'encoding', 'value')))) {
            $attachInlineBinary = true;
            if ('fmttype' == $attrKey) {
              $attachfmttype = $attrKey . '=' . $attrValue;
            }
            continue;
          }
          elseif (('categories' == $label) && ('language' == $attrKey)) {
            $categoriesAttrLang = $attrKey . '=' . $attrValue;
          }
          else {
            $attributes .= (empty($attributes)) ? ' ' : $this->attributeDelimiter . ' ';
            $attributes .= (!empty($attrKey)) ? $attrKey . '=' : null;
            if (('"' == substr($attrValue, 0, 1)) && ('"' == substr($attrValue, -1))) {
              $attrValue = substr($attrValue, 1, (strlen($attrValue) - 2));
              $attrValue = str_replace('"', '', $attrValue);
            }
            $attributes .= '"' . htmlspecialchars($attrValue) . '"';
          }
        }
      }
      else {
        $attributes = str_replace($this->intAttrDelimiter, $this->attributeDelimiter, $attributes);
      }
    }
    if (((('attach' == $label) && !$attachInlineBinary) ||
        (in_array($label, array('tzurl', 'url')))) && ('xcal' == $this->format)) {
      $pos              = strrpos($content, "/");
      $docname          = ($pos !== false) ? substr($content, (1 - strlen($content) + $pos)) : $content;
      $this->xcaldecl[] = array('xmldecl' => 'ENTITY'
      , 'uri'                             => $docname
      , 'ref'                             => 'SYSTEM'
      , 'external'                        => $content
      , 'type'                            => 'NDATA'
      , 'type2'                           => 'BINERY');
      $attributes       .= (empty($attributes)) ? ' ' : $this->attributeDelimiter . ' ';
      $attributes       .= 'uri="' . $docname . '"';
      $content          = null;
      if ('attach' == $label) {
        $attributes = str_replace($this->attributeDelimiter, $this->intAttrDelimiter, $attributes);
        $content    = $this->_createElement('extref', $attributes, null);
        $attributes = null;
      }
    }
    elseif (('attach' == $label) && $attachInlineBinary && ('xcal' == $this->format)) {
      $content = $this->nl . $this->_createElement('b64bin', $attachfmttype, $content); // max one attribute
    }
    $output .= $attributes;
    if (!$content && ('0' != $content)) {
      switch ($this->format) {
        case 'xcal':
          $output .= ' /';
          $output .= $this->elementStart2;

          return $output;
          break;
        default:
          $output .= $this->elementStart2 . $this->valueInit;

          return $this->_size75($output);
          break;
      }
    }
    $output .= $this->elementStart2;
    $output .= $this->valueInit . $content;
    switch ($this->format) {
      case 'xcal':
        return $output . $this->elementEnd1 . $label . $this->elementEnd2;
        break;
      default:
        return $this->_size75($output);
        break;
    }
  }

  /**
   * creates formatted output for calendar component property parameters
   *
   * @param array $params  optional
   * @param array $ctrKeys optional
   *
   * @return string
   * @since  2.6.33 - 2010-12-18
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function _createParams($params = array(), $ctrKeys = array()) {
    if (!is_array($params) || empty($params)) {
      $params = array();
    }
    $attrLANG    = $attr1 = $attr2 = $lang = null;
    $CNattrKey   = (in_array('CN', $ctrKeys)) ? true : false;
    $LANGattrKey = (in_array('LANGUAGE', $ctrKeys)) ? true : false;
    $CNattrExist = $LANGattrExist = false;
    $xparams     = array();
    foreach ($params as $paramKey => $paramValue) {
      if (ctype_digit((string)$paramKey)) {
        $xparams[] = $paramValue;
        continue;
      }
      $paramKey = strtoupper($paramKey);
      if (!in_array($paramKey, array('ALTREP', 'CN', 'DIR', 'ENCODING', 'FMTTYPE', 'LANGUAGE', 'RANGE', 'RELTYPE', 'SENT-BY', 'TZID', 'VALUE'))) {
        $xparams[$paramKey] = $paramValue;
      }
      else {
        $params[$paramKey] = $paramValue;
      }
    }
    ksort($xparams, SORT_STRING);
    foreach ($xparams as $paramKey => $paramValue) {
      if (ctype_digit((string)$paramKey)) {
        $attr2 .= $this->intAttrDelimiter . $paramValue;
      }
      else {
        $attr2 .= $this->intAttrDelimiter . "$paramKey=$paramValue";
      }
    }
    if (isset($params['FMTTYPE']) && !in_array('FMTTYPE', $ctrKeys)) {
      $attr1 .= $this->intAttrDelimiter . 'FMTTYPE=' . $params['FMTTYPE'] . $attr2;
      $attr2 = null;
    }
    if (isset($params['ENCODING']) && !in_array('ENCODING', $ctrKeys)) {
      if (!empty($attr2)) {
        $attr1 .= $attr2;
        $attr2 = null;
      }
      $attr1 .= $this->intAttrDelimiter . 'ENCODING=' . $params['ENCODING'];
    }
    if (isset($params['VALUE']) && !in_array('VALUE', $ctrKeys)) {
      $attr1 .= $this->intAttrDelimiter . 'VALUE=' . $params['VALUE'];
    }
    if (isset($params['TZID']) && !in_array('TZID', $ctrKeys)) {
      $attr1 .= $this->intAttrDelimiter . 'TZID=' . $params['TZID'];
    }
    if (isset($params['RANGE']) && !in_array('RANGE', $ctrKeys)) {
      $attr1 .= $this->intAttrDelimiter . 'RANGE=' . $params['RANGE'];
    }
    if (isset($params['RELTYPE']) && !in_array('RELTYPE', $ctrKeys)) {
      $attr1 .= $this->intAttrDelimiter . 'RELTYPE=' . $params['RELTYPE'];
    }
    if (isset($params['CN']) && $CNattrKey) {
      $attr1       = $this->intAttrDelimiter . 'CN="' . $params['CN'] . '"';
      $CNattrExist = true;
    }
    if (isset($params['DIR']) && in_array('DIR', $ctrKeys)) {
      $attr1 .= $this->intAttrDelimiter . 'DIR="' . $params['DIR'] . '"';
    }
    if (isset($params['SENT-BY']) && in_array('SENT-BY', $ctrKeys)) {
      $attr1 .= $this->intAttrDelimiter . 'SENT-BY="' . $params['SENT-BY'] . '"';
    }
    if (isset($params['ALTREP']) && in_array('ALTREP', $ctrKeys)) {
      $attr1 .= $this->intAttrDelimiter . 'ALTREP="' . $params['ALTREP'] . '"';
    }
    if (isset($params['LANGUAGE']) && $LANGattrKey) {
      $attrLANG      .= $this->intAttrDelimiter . 'LANGUAGE=' . $params['LANGUAGE'];
      $LANGattrExist = true;
    }
    if (!$LANGattrExist) {
      $lang = $this->getConfig('language');
      if (($CNattrExist || $LANGattrKey) && $lang) {
        $attrLANG .= $this->intAttrDelimiter . 'LANGUAGE=' . $lang;
      }
    }

    return $attr1 . $attrLANG . $attr2;
  }

  /**
   * creates formatted output for calendar component property data value type recur
   *
   * @param array $recurlabel
   * @param array $recurdata
   *
   * @return string
   * @since  2.4.8 - 2008-10-22
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function _format_recur($recurlabel, $recurdata) {
    $output = null;
    foreach ($recurdata as $therule) {
      if (empty($therule['value'])) {
        if ($this->getConfig('allowEmpty')) {
          $output .= $this->_createElement($recurlabel);
        }
        continue;
      }
      $attributes = (isset($therule['params'])) ? $this->_createParams($therule['params']) : null;
      $content1   = $content2 = null;
      foreach ($therule['value'] as $rulelabel => $rulevalue) {
        switch ($rulelabel) {
          case 'FREQ':
          {
            $content1 .= "FREQ=$rulevalue";
            break;
          }
          case 'UNTIL':
          {
            $content2 .= ";UNTIL=";
            $content2 .= iCalUtilityFunctions::_format_date_time($rulevalue);
            break;
          }
          case 'COUNT':
          case 'INTERVAL':
          case 'WKST':
          {
            $content2 .= ";$rulelabel=$rulevalue";
            break;
          }
          case 'BYSECOND':
          case 'BYMINUTE':
          case 'BYHOUR':
          case 'BYMONTHDAY':
          case 'BYYEARDAY':
          case 'BYWEEKNO':
          case 'BYMONTH':
          case 'BYSETPOS':
          {
            $content2 .= ";$rulelabel=";
            if (is_array($rulevalue)) {
              foreach ($rulevalue as $vix => $valuePart) {
                $content2 .= ($vix) ? ',' : null;
                $content2 .= $valuePart;
              }
            }
            else {
              $content2 .= $rulevalue;
            }
            break;
          }
          case 'BYDAY':
          {
            $content2 .= ";$rulelabel=";
            $bydaycnt = 0;
            foreach ($rulevalue as $vix => $valuePart) {
              $content21 = $content22 = null;
              if (is_array($valuePart)) {
                $content2 .= ($bydaycnt) ? ',' : null;
                foreach ($valuePart as $vix2 => $valuePart2) {
                  if ('DAY' != strtoupper($vix2)) {
                    $content21 .= $valuePart2;
                  }
                  else {
                    $content22 .= $valuePart2;
                  }
                }
                $content2 .= $content21 . $content22;
                $bydaycnt++;
              }
              else {
                $content2 .= ($bydaycnt) ? ',' : null;
                if ('DAY' != strtoupper($vix)) {
                  $content21 .= $valuePart;
                }
                else {
                  $content22 .= $valuePart;
                  $bydaycnt++;
                }
                $content2 .= $content21 . $content22;
              }
            }
            break;
          }
          default:
          {
            $content2 .= ";$rulelabel=$rulevalue";
            break;
          }
        }
      }
      $output .= $this->_createElement($recurlabel, $attributes, $content1 . $content2);
    }

    return $output;
  }

  /**
   * check if property not exists within component
   *
   * @param string $propName
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.5.1 - 2008-10-15
   */
  function _notExistProp($propName) {
    if (empty($propName)) {
      return false;
    } // when deleting x-prop, an empty propName may be used=allowed
    $propName = strtolower($propName);
    if ('last-modified' == $propName) {
      if (!isset($this->lastmodified)) {
        return true;
      }
    }
    elseif ('percent-complete' == $propName) {
      if (!isset($this->percentcomplete)) {
        return true;
      }
    }
    elseif ('recurrence-id' == $propName) {
      if (!isset($this->recurrenceid)) {
        return true;
      }
    }
    elseif ('related-to' == $propName) {
      if (!isset($this->relatedto)) {
        return true;
      }
    }
    elseif ('request-status' == $propName) {
      if (!isset($this->requeststatus)) {
        return true;
      }
    }
    elseif (('x-' != substr($propName, 0, 2)) && !isset($this->$propName)) {
      return true;
    }

    return false;
  }
  /*********************************************************************************/
  /*********************************************************************************/
  /**
   * get general component config variables or info about subcomponents
   *
   * @param mixed $config
   *
   * @return value
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.9.6 - 2011-05-14
   */
  function getConfig($config = false) {
    if (!$config) {
      $return               = array();
      $return['ALLOWEMPTY'] = $this->getConfig('ALLOWEMPTY');
      $return['FORMAT']     = $this->getConfig('FORMAT');
      if (false !== ($lang = $this->getConfig('LANGUAGE'))) {
        $return['LANGUAGE'] = $lang;
      }
      $return['NEWLINECHAR'] = $this->getConfig('NEWLINECHAR');
      $return['TZTD']        = $this->getConfig('TZID');
      $return['UNIQUE_ID']   = $this->getConfig('UNIQUE_ID');

      return $return;
    }
    switch (strtoupper($config)) {
      case 'ALLOWEMPTY':
        return $this->allowEmpty;
        break;
      case 'COMPSINFO':
        unset($this->compix);
        $info = array();
        if (isset($this->components)) {
          foreach ($this->components as $cix => $component) {
            if (empty($component)) {
              continue;
            }
            $info[$cix]['ordno'] = $cix + 1;
            $info[$cix]['type']  = $component->objName;
            $info[$cix]['uid']   = $component->getProperty('uid');
            $info[$cix]['props'] = $component->getConfig('propinfo');
            $info[$cix]['sub']   = $component->getConfig('compsinfo');
          }
        }

        return $info;
        break;
      case 'FORMAT':
        return $this->format;
        break;
      case 'LANGUAGE':
// get language for calendar component as defined in [RFC 1766]
        return $this->language;
        break;
      case 'NL':
      case 'NEWLINECHAR':
        return $this->nl;
        break;
      case 'PROPINFO':
        $output = array();
        if (!in_array($this->objName, array('valarm', 'vtimezone', 'standard', 'daylight'))) {
          if (empty($this->uid['value'])) {
            $this->_makeuid();
          }
          $output['UID'] = 1;
        }
        if (!empty($this->dtstamp)) {
          $output['DTSTAMP'] = 1;
        }
        if (!empty($this->summary)) {
          $output['SUMMARY'] = 1;
        }
        if (!empty($this->description)) {
          $output['DESCRIPTION'] = count($this->description);
        }
        if (!empty($this->dtstart)) {
          $output['DTSTART'] = 1;
        }
        if (!empty($this->dtend)) {
          $output['DTEND'] = 1;
        }
        if (!empty($this->due)) {
          $output['DUE'] = 1;
        }
        if (!empty($this->duration)) {
          $output['DURATION'] = 1;
        }
        if (!empty($this->rrule)) {
          $output['RRULE'] = count($this->rrule);
        }
        if (!empty($this->rdate)) {
          $output['RDATE'] = count($this->rdate);
        }
        if (!empty($this->exdate)) {
          $output['EXDATE'] = count($this->exdate);
        }
        if (!empty($this->exrule)) {
          $output['EXRULE'] = count($this->exrule);
        }
        if (!empty($this->action)) {
          $output['ACTION'] = 1;
        }
        if (!empty($this->attach)) {
          $output['ATTACH'] = count($this->attach);
        }
        if (!empty($this->attendee)) {
          $output['ATTENDEE'] = count($this->attendee);
        }
        if (!empty($this->categories)) {
          $output['CATEGORIES'] = count($this->categories);
        }
        if (!empty($this->class)) {
          $output['CLASS'] = 1;
        }
        if (!empty($this->comment)) {
          $output['COMMENT'] = count($this->comment);
        }
        if (!empty($this->completed)) {
          $output['COMPLETED'] = 1;
        }
        if (!empty($this->contact)) {
          $output['CONTACT'] = count($this->contact);
        }
        if (!empty($this->created)) {
          $output['CREATED'] = 1;
        }
        if (!empty($this->freebusy)) {
          $output['FREEBUSY'] = count($this->freebusy);
        }
        if (!empty($this->geo)) {
          $output['GEO'] = 1;
        }
        if (!empty($this->lastmodified)) {
          $output['LAST-MODIFIED'] = 1;
        }
        if (!empty($this->location)) {
          $output['LOCATION'] = 1;
        }
        if (!empty($this->organizer)) {
          $output['ORGANIZER'] = 1;
        }
        if (!empty($this->percentcomplete)) {
          $output['PERCENT-COMPLETE'] = 1;
        }
        if (!empty($this->priority)) {
          $output['PRIORITY'] = 1;
        }
        if (!empty($this->recurrenceid)) {
          $output['RECURRENCE-ID'] = 1;
        }
        if (!empty($this->relatedto)) {
          $output['RELATED-TO'] = count($this->relatedto);
        }
        if (!empty($this->repeat)) {
          $output['REPEAT'] = 1;
        }
        if (!empty($this->requeststatus)) {
          $output['REQUEST-STATUS'] = count($this->requeststatus);
        }
        if (!empty($this->resources)) {
          $output['RESOURCES'] = count($this->resources);
        }
        if (!empty($this->sequence)) {
          $output['SEQUENCE'] = 1;
        }
        if (!empty($this->sequence)) {
          $output['SEQUENCE'] = 1;
        }
        if (!empty($this->status)) {
          $output['STATUS'] = 1;
        }
        if (!empty($this->transp)) {
          $output['TRANSP'] = 1;
        }
        if (!empty($this->trigger)) {
          $output['TRIGGER'] = 1;
        }
        if (!empty($this->tzid)) {
          $output['TZID'] = 1;
        }
        if (!empty($this->tzname)) {
          $output['TZNAME'] = count($this->tzname);
        }
        if (!empty($this->tzoffsetfrom)) {
          $output['TZOFFSETFROM'] = 1;
        }
        if (!empty($this->tzoffsetto)) {
          $output['TZOFFSETTO'] = 1;
        }
        if (!empty($this->tzurl)) {
          $output['TZURL'] = 1;
        }
        if (!empty($this->url)) {
          $output['URL'] = 1;
        }
        if (!empty($this->xprop)) {
          $output['X-PROP'] = count($this->xprop);
        }

        return $output;
        break;
      case 'TZID':
        return $this->dtzid;
        break;
      case 'UNIQUE_ID':
        if (empty($this->unique_id)) {
          $this->unique_id = (isset($_SERVER['SERVER_NAME'])) ? gethostbyname($_SERVER['SERVER_NAME']) : 'localhost';
        }

        return $this->unique_id;
        break;
    }
  }

  /**
   * general component config setting
   *
   * @param mixed  $config
   * @param string $value
   * @param bool   $softUpdate
   *
   * @return void
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.9.6 - 2011-05-14
   */
  function setConfig($config, $value = false, $softUpdate = false) {
    if (is_array($config)) {
      foreach ($config as $cKey => $cValue) {
        if (false === $this->setConfig($cKey, $cValue, $softUpdate)) {
          return false;
        }
      }

      return true;
    }
    $res = false;
    switch (strtoupper($config)) {
      case 'ALLOWEMPTY':
        $this->allowEmpty = $value;
        $subcfg           = array('ALLOWEMPTY' => $value);
        $res              = true;
        break;
      case 'FORMAT':
        $value        = trim(strtolower($value));
        $this->format = $value;
        $this->_createFormat();
        $subcfg = array('FORMAT' => $value);
        $res    = true;
        break;
      case 'LANGUAGE':
// set language for calendar component as defined in [RFC 1766]
        $value = trim($value);
        if (empty($this->language) || !$softUpdate) {
          $this->language = $value;
        }
        $subcfg = array('LANGUAGE' => $value);
        $res    = true;
        break;
      case 'NL':
      case 'NEWLINECHAR':
        $this->nl = $value;
        $subcfg   = array('NL' => $value);
        $res      = true;
        break;
      case 'TZID':
        $this->dtzid = $value;
        $subcfg      = array('TZID' => $value);
        $res         = true;
        break;
      case 'UNIQUE_ID':
        $value           = trim($value);
        $this->unique_id = $value;
        $subcfg          = array('UNIQUE_ID' => $value);
        $res             = true;
        break;
      default:  // any unvalid config key.. .
        return true;
    }
    if (!$res) {
      return false;
    }
    if (isset($subcfg) && !empty($this->components)) {
      foreach ($subcfg as $cfgkey => $cfgvalue) {
        foreach ($this->components as $cix => $component) {
          $res = $component->setConfig($cfgkey, $cfgvalue, $softUpdate);
          if (!$res) {
            break 2;
          }
          $this->components[$cix] = $component->copy(); // PHP4 compliant
        }
      }
    }

    return $res;
  }
  /*********************************************************************************/
  /**
   * delete component property value
   *
   * @param mixed $propName , bool FALSE => X-property
   * @param int   $propix   , optional, if specific property is wanted in case of multiply occurences
   *
   * @return bool, if successfull delete TRUE
   * @since  2.8.8 - 2011-03-15
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function deleteProperty($propName = false, $propix = false) {
    if ($this->_notExistProp($propName)) {
      return false;
    }
    $propName = strtoupper($propName);
    if (in_array($propName, array('ATTACH', 'ATTENDEE', 'CATEGORIES', 'COMMENT', 'CONTACT', 'DESCRIPTION', 'EXDATE', 'EXRULE',
      'FREEBUSY', 'RDATE', 'RELATED-TO', 'RESOURCES', 'RRULE', 'REQUEST-STATUS', 'TZNAME', 'X-PROP'))) {
      if (!$propix) {
        $propix = (isset($this->propdelix[$propName]) && ('X-PROP' != $propName)) ? $this->propdelix[$propName] + 2 : 1;
      }
      $this->propdelix[$propName] = --$propix;
    }
    $return = false;
    switch ($propName) {
      case 'ACTION':
        if (!empty($this->action)) {
          $this->action = '';
          $return       = true;
        }
        break;
      case 'ATTACH':
        return $this->deletePropertyM($this->attach, $this->propdelix[$propName]);
        break;
      case 'ATTENDEE':
        return $this->deletePropertyM($this->attendee, $this->propdelix[$propName]);
        break;
      case 'CATEGORIES':
        return $this->deletePropertyM($this->categories, $this->propdelix[$propName]);
        break;
      case 'CLASS':
        if (!empty($this->class)) {
          $this->class = '';
          $return      = true;
        }
        break;
      case 'COMMENT':
        return $this->deletePropertyM($this->comment, $this->propdelix[$propName]);
        break;
      case 'COMPLETED':
        if (!empty($this->completed)) {
          $this->completed = '';
          $return          = true;
        }
        break;
      case 'CONTACT':
        return $this->deletePropertyM($this->contact, $this->propdelix[$propName]);
        break;
      case 'CREATED':
        if (!empty($this->created)) {
          $this->created = '';
          $return        = true;
        }
        break;
      case 'DESCRIPTION':
        return $this->deletePropertyM($this->description, $this->propdelix[$propName]);
        break;
      case 'DTEND':
        if (!empty($this->dtend)) {
          $this->dtend = '';
          $return      = true;
        }
        break;
      case 'DTSTAMP':
        if (in_array($this->objName, array('valarm', 'vtimezone', 'standard', 'daylight'))) {
          return false;
        }
        if (!empty($this->dtstamp)) {
          $this->dtstamp = '';
          $return        = true;
        }
        break;
      case 'DTSTART':
        if (!empty($this->dtstart)) {
          $this->dtstart = '';
          $return        = true;
        }
        break;
      case 'DUE':
        if (!empty($this->due)) {
          $this->due = '';
          $return    = true;
        }
        break;
      case 'DURATION':
        if (!empty($this->duration)) {
          $this->duration = '';
          $return         = true;
        }
        break;
      case 'EXDATE':
        return $this->deletePropertyM($this->exdate, $this->propdelix[$propName]);
        break;
      case 'EXRULE':
        return $this->deletePropertyM($this->exrule, $this->propdelix[$propName]);
        break;
      case 'FREEBUSY':
        return $this->deletePropertyM($this->freebusy, $this->propdelix[$propName]);
        break;
      case 'GEO':
        if (!empty($this->geo)) {
          $this->geo = '';
          $return    = true;
        }
        break;
      case 'LAST-MODIFIED':
        if (!empty($this->lastmodified)) {
          $this->lastmodified = '';
          $return             = true;
        }
        break;
      case 'LOCATION':
        if (!empty($this->location)) {
          $this->location = '';
          $return         = true;
        }
        break;
      case 'ORGANIZER':
        if (!empty($this->organizer)) {
          $this->organizer = '';
          $return          = true;
        }
        break;
      case 'PERCENT-COMPLETE':
        if (!empty($this->percentcomplete)) {
          $this->percentcomplete = '';
          $return                = true;
        }
        break;
      case 'PRIORITY':
        if (!empty($this->priority)) {
          $this->priority = '';
          $return         = true;
        }
        break;
      case 'RDATE':
        return $this->deletePropertyM($this->rdate, $this->propdelix[$propName]);
        break;
      case 'RECURRENCE-ID':
        if (!empty($this->recurrenceid)) {
          $this->recurrenceid = '';
          $return             = true;
        }
        break;
      case 'RELATED-TO':
        return $this->deletePropertyM($this->relatedto, $this->propdelix[$propName]);
        break;
      case 'REPEAT':
        if (!empty($this->repeat)) {
          $this->repeat = '';
          $return       = true;
        }
        break;
      case 'REQUEST-STATUS':
        return $this->deletePropertyM($this->requeststatus, $this->propdelix[$propName]);
        break;
      case 'RESOURCES':
        return $this->deletePropertyM($this->resources, $this->propdelix[$propName]);
        break;
      case 'RRULE':
        return $this->deletePropertyM($this->rrule, $this->propdelix[$propName]);
        break;
      case 'SEQUENCE':
        if (!empty($this->sequence)) {
          $this->sequence = '';
          $return         = true;
        }
        break;
      case 'STATUS':
        if (!empty($this->status)) {
          $this->status = '';
          $return       = true;
        }
        break;
      case 'SUMMARY':
        if (!empty($this->summary)) {
          $this->summary = '';
          $return        = true;
        }
        break;
      case 'TRANSP':
        if (!empty($this->transp)) {
          $this->transp = '';
          $return       = true;
        }
        break;
      case 'TRIGGER':
        if (!empty($this->trigger)) {
          $this->trigger = '';
          $return        = true;
        }
        break;
      case 'TZID':
        if (!empty($this->tzid)) {
          $this->tzid = '';
          $return     = true;
        }
        break;
      case 'TZNAME':
        return $this->deletePropertyM($this->tzname, $this->propdelix[$propName]);
        break;
      case 'TZOFFSETFROM':
        if (!empty($this->tzoffsetfrom)) {
          $this->tzoffsetfrom = '';
          $return             = true;
        }
        break;
      case 'TZOFFSETTO':
        if (!empty($this->tzoffsetto)) {
          $this->tzoffsetto = '';
          $return           = true;
        }
        break;
      case 'TZURL':
        if (!empty($this->tzurl)) {
          $this->tzurl = '';
          $return      = true;
        }
        break;
      case 'UID':
        if (in_array($this->objName, array('valarm', 'vtimezone', 'standard', 'daylight'))) {
          return false;
        }
        if (!empty($this->uid)) {
          $this->uid = '';
          $return    = true;
        }
        break;
      case 'URL':
        if (!empty($this->url)) {
          $this->url = '';
          $return    = true;
        }
        break;
      default:
        $reduced = '';
        if ($propName != 'X-PROP') {
          if (!isset($this->xprop[$propName])) {
            return false;
          }
          foreach ($this->xprop as $k => $a) {
            if (($k != $propName) && !empty($a)) {
              $reduced[$k] = $a;
            }
          }
        }
        else {
          if (count($this->xprop) <= $propix) {
            unset($this->propdelix[$propName]);

            return false;
          }
          $xpropno = 0;
          foreach ($this->xprop as $xpropkey => $xpropvalue) {
            if ($propix != $xpropno) {
              $reduced[$xpropkey] = $xpropvalue;
            }
            $xpropno++;
          }
        }
        $this->xprop = $reduced;
        if (empty($this->xprop)) {
          unset($this->propdelix[$propName]);

          return false;
        }

        return true;
    }

    return $return;
  }
  /*********************************************************************************/
  /**
   * delete component property value, fixing components with multiple occurencies
   *
   * @param array $multiprop , reference to a component property
   * @param int   $propix    , reference to removal counter
   *
   * @return bool TRUE
   * @since  2.8.8 - 2011-03-15
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function deletePropertyM(& $multiprop, & $propix) {
    if (isset($multiprop[$propix])) {
      unset($multiprop[$propix]);
    }
    if (empty($multiprop)) {
      $multiprop = '';
      unset($propix);

      return false;
    }
    else {
      return true;
    }
  }

  /**
   * get component property value/params
   *
   * if property has multiply values, consequtive function calls are needed
   *
   * @param string $propName  , optional
   * @param int @propix, optional, if specific property is wanted in case of multiply occurences
   * @param bool   $inclParam =FALSE
   * @param bool   $specform  =FALSE
   *
   * @return mixed
   * @since  2.10.1 - 2011-07-16
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function getProperty($propName = false, $propix = false, $inclParam = false, $specform = false) {
    if ($this->_notExistProp($propName)) {
      return false;
    }
    $propName = ($propName) ? strtoupper($propName) : 'X-PROP';
    if (in_array($propName, array('ATTACH', 'ATTENDEE', 'CATEGORIES', 'COMMENT', 'CONTACT', 'DESCRIPTION', 'EXDATE', 'EXRULE',
      'FREEBUSY', 'RDATE', 'RELATED-TO', 'RESOURCES', 'RRULE', 'REQUEST-STATUS', 'TZNAME', 'X-PROP'))) {
      if (!$propix) {
        $propix = (isset($this->propix[$propName])) ? $this->propix[$propName] + 2 : 1;
      }
      $this->propix[$propName] = --$propix;
    }
    switch ($propName) {
      case 'ACTION':
        if (!empty($this->action['value'])) {
          return ($inclParam) ? $this->action : $this->action['value'];
        }
        break;
      case 'ATTACH':
        $ak = (is_array($this->attach)) ? array_keys($this->attach) : array();
        while (is_array($this->attach) && !isset($this->attach[$propix]) && (0 < count($this->attach)) && ($propix < end($ak))) {
          $propix++;
        }
        if (!isset($this->attach[$propix])) {
          unset($this->propix[$propName]);

          return false;
        }

        return ($inclParam) ? $this->attach[$propix] : $this->attach[$propix]['value'];
        break;
      case 'ATTENDEE':
        $ak = (is_array($this->attendee)) ? array_keys($this->attendee) : array();
        while (is_array($this->attendee) && !isset($this->attendee[$propix]) && (0 < count($this->attendee)) && ($propix < end($ak))) {
          $propix++;
        }
        if (!isset($this->attendee[$propix])) {
          unset($this->propix[$propName]);

          return false;
        }

        return ($inclParam) ? $this->attendee[$propix] : $this->attendee[$propix]['value'];
        break;
      case 'CATEGORIES':
        $ak = (is_array($this->categories)) ? array_keys($this->categories) : array();
        while (is_array($this->categories) && !isset($this->categories[$propix]) && (0 < count($this->categories)) && ($propix < end($ak))) {
          $propix++;
        }
        if (!isset($this->categories[$propix])) {
          unset($this->propix[$propName]);

          return false;
        }

        return ($inclParam) ? $this->categories[$propix] : $this->categories[$propix]['value'];
        break;
      case 'CLASS':
        if (!empty($this->class['value'])) {
          return ($inclParam) ? $this->class : $this->class['value'];
        }
        break;
      case 'COMMENT':
        $ak = (is_array($this->comment)) ? array_keys($this->comment) : array();
        while (is_array($this->comment) && !isset($this->comment[$propix]) && (0 < count($this->comment)) && ($propix < end($ak))) {
          $propix++;
        }
        if (!isset($this->comment[$propix])) {
          unset($this->propix[$propName]);

          return false;
        }

        return ($inclParam) ? $this->comment[$propix] : $this->comment[$propix]['value'];
        break;
      case 'COMPLETED':
        if (!empty($this->completed['value'])) {
          return ($inclParam) ? $this->completed : $this->completed['value'];
        }
        break;
      case 'CONTACT':
        $ak = (is_array($this->contact)) ? array_keys($this->contact) : array();
        while (is_array($this->contact) && !isset($this->contact[$propix]) && (0 < count($this->contact)) && ($propix < end($ak))) {
          $propix++;
        }
        if (!isset($this->contact[$propix])) {
          unset($this->propix[$propName]);

          return false;
        }

        return ($inclParam) ? $this->contact[$propix] : $this->contact[$propix]['value'];
        break;
      case 'CREATED':
        if (!empty($this->created['value'])) {
          return ($inclParam) ? $this->created : $this->created['value'];
        }
        break;
      case 'DESCRIPTION':
        $ak = (is_array($this->description)) ? array_keys($this->description) : array();
        while (is_array($this->description) && !isset($this->description[$propix]) && (0 < count($this->description)) && ($propix < end($ak))) {
          $propix++;
        }
        if (!isset($this->description[$propix])) {
          unset($this->propix[$propName]);

          return false;
        }

        return ($inclParam) ? $this->description[$propix] : $this->description[$propix]['value'];
        break;
      case 'DTEND':
        if (!empty($this->dtend['value'])) {
          return ($inclParam) ? $this->dtend : $this->dtend['value'];
        }
        break;
      case 'DTSTAMP':
        if (in_array($this->objName, array('valarm', 'vtimezone', 'standard', 'daylight'))) {
          return;
        }
        if (!isset($this->dtstamp['value'])) {
          $this->_makeDtstamp();
        }

        return ($inclParam) ? $this->dtstamp : $this->dtstamp['value'];
        break;
      case 'DTSTART':
        if (!empty($this->dtstart['value'])) {
          return ($inclParam) ? $this->dtstart : $this->dtstart['value'];
        }
        break;
      case 'DUE':
        if (!empty($this->due['value'])) {
          return ($inclParam) ? $this->due : $this->due['value'];
        }
        break;
      case 'DURATION':
        if (!isset($this->duration['value'])) {
          return false;
        }
        $value = ($specform && isset($this->dtstart['value']) && isset($this->duration['value'])) ? iCalUtilityFunctions::_duration2date($this->dtstart['value'], $this->duration['value']) : $this->duration['value'];

        return ($inclParam) ? array('value' => $value, 'params' => $this->duration['params']) : $value;
        break;
      case 'EXDATE':
        $ak = (is_array($this->exdate)) ? array_keys($this->exdate) : array();
        while (is_array($this->exdate) && !isset($this->exdate[$propix]) && (0 < count($this->exdate)) && ($propix < end($ak))) {
          $propix++;
        }
        if (!isset($this->exdate[$propix])) {
          unset($this->propix[$propName]);

          return false;
        }

        return ($inclParam) ? $this->exdate[$propix] : $this->exdate[$propix]['value'];
        break;
      case 'EXRULE':
        $ak = (is_array($this->exrule)) ? array_keys($this->exrule) : array();
        while (is_array($this->exrule) && !isset($this->exrule[$propix]) && (0 < count($this->exrule)) && ($propix < end($ak))) {
          $propix++;
        }
        if (!isset($this->exrule[$propix])) {
          unset($this->propix[$propName]);

          return false;
        }

        return ($inclParam) ? $this->exrule[$propix] : $this->exrule[$propix]['value'];
        break;
      case 'FREEBUSY':
        $ak = (is_array($this->freebusy)) ? array_keys($this->freebusy) : array();
        while (is_array($this->freebusy) && !isset($this->freebusy[$propix]) && (0 < count($this->freebusy)) && ($propix < end($ak))) {
          $propix++;
        }
        if (!isset($this->freebusy[$propix])) {
          unset($this->propix[$propName]);

          return false;
        }

        return ($inclParam) ? $this->freebusy[$propix] : $this->freebusy[$propix]['value'];
        break;
      case 'GEO':
        if (!empty($this->geo['value'])) {
          return ($inclParam) ? $this->geo : $this->geo['value'];
        }
        break;
      case 'LAST-MODIFIED':
        if (!empty($this->lastmodified['value'])) {
          return ($inclParam) ? $this->lastmodified : $this->lastmodified['value'];
        }
        break;
      case 'LOCATION':
        if (!empty($this->location['value'])) {
          return ($inclParam) ? $this->location : $this->location['value'];
        }
        break;
      case 'ORGANIZER':
        if (!empty($this->organizer['value'])) {
          return ($inclParam) ? $this->organizer : $this->organizer['value'];
        }
        break;
      case 'PERCENT-COMPLETE':
        if (!empty($this->percentcomplete['value']) || (isset($this->percentcomplete['value']) && ('0' == $this->percentcomplete['value']))) {
          return ($inclParam) ? $this->percentcomplete : $this->percentcomplete['value'];
        }
        break;
      case 'PRIORITY':
        if (!empty($this->priority['value']) || (isset($this->priority['value']) && ('0' == $this->priority['value']))) {
          return ($inclParam) ? $this->priority : $this->priority['value'];
        }
        break;
      case 'RDATE':
        $ak = (is_array($this->rdate)) ? array_keys($this->rdate) : array();
        while (is_array($this->rdate) && !isset($this->rdate[$propix]) && (0 < count($this->rdate)) && ($propix < end($ak))) {
          $propix++;
        }
        if (!isset($this->rdate[$propix])) {
          unset($this->propix[$propName]);

          return false;
        }

        return ($inclParam) ? $this->rdate[$propix] : $this->rdate[$propix]['value'];
        break;
      case 'RECURRENCE-ID':
        if (!empty($this->recurrenceid['value'])) {
          return ($inclParam) ? $this->recurrenceid : $this->recurrenceid['value'];
        }
        break;
      case 'RELATED-TO':
        $ak = (is_array($this->relatedto)) ? array_keys($this->relatedto) : array();
        while (is_array($this->relatedto) && !isset($this->relatedto[$propix]) && (0 < count($this->relatedto)) && ($propix < end($ak))) {
          $propix++;
        }
        if (!isset($this->relatedto[$propix])) {
          unset($this->propix[$propName]);

          return false;
        }

        return ($inclParam) ? $this->relatedto[$propix] : $this->relatedto[$propix]['value'];
        break;
      case 'REPEAT':
        if (!empty($this->repeat['value']) || (isset($this->repeat['value']) && ('0' == $this->repeat['value']))) {
          return ($inclParam) ? $this->repeat : $this->repeat['value'];
        }
        break;
      case 'REQUEST-STATUS':
        $ak = (is_array($this->requeststatus)) ? array_keys($this->requeststatus) : array();
        while (is_array($this->requeststatus) && !isset($this->requeststatus[$propix]) && (0 < count($this->requeststatus)) && ($propix < end($ak))) {
          $propix++;
        }
        if (!isset($this->requeststatus[$propix])) {
          unset($this->propix[$propName]);

          return false;
        }

        return ($inclParam) ? $this->requeststatus[$propix] : $this->requeststatus[$propix]['value'];
        break;
      case 'RESOURCES':
        $ak = (is_array($this->resources)) ? array_keys($this->resources) : array();
        while (is_array($this->resources) && !isset($this->resources[$propix]) && (0 < count($this->resources)) && ($propix < end($ak))) {
          $propix++;
        }
        if (!isset($this->resources[$propix])) {
          unset($this->propix[$propName]);

          return false;
        }

        return ($inclParam) ? $this->resources[$propix] : $this->resources[$propix]['value'];
        break;
      case 'RRULE':
        $ak = (is_array($this->rrule)) ? array_keys($this->rrule) : array();
        while (is_array($this->rrule) && !isset($this->rrule[$propix]) && (0 < count($this->rrule)) && ($propix < end($ak))) {
          $propix++;
        }
        if (!isset($this->rrule[$propix])) {
          unset($this->propix[$propName]);

          return false;
        }

        return ($inclParam) ? $this->rrule[$propix] : $this->rrule[$propix]['value'];
        break;
      case 'SEQUENCE':
        if (isset($this->sequence['value']) && (isset($this->sequence['value']) && ('0' <= $this->sequence['value']))) {
          return ($inclParam) ? $this->sequence : $this->sequence['value'];
        }
        break;
      case 'STATUS':
        if (!empty($this->status['value'])) {
          return ($inclParam) ? $this->status : $this->status['value'];
        }
        break;
      case 'SUMMARY':
        if (!empty($this->summary['value'])) {
          return ($inclParam) ? $this->summary : $this->summary['value'];
        }
        break;
      case 'TRANSP':
        if (!empty($this->transp['value'])) {
          return ($inclParam) ? $this->transp : $this->transp['value'];
        }
        break;
      case 'TRIGGER':
        if (!empty($this->trigger['value'])) {
          return ($inclParam) ? $this->trigger : $this->trigger['value'];
        }
        break;
      case 'TZID':
        if (!empty($this->tzid['value'])) {
          return ($inclParam) ? $this->tzid : $this->tzid['value'];
        }
        break;
      case 'TZNAME':
        $ak = (is_array($this->tzname)) ? array_keys($this->tzname) : array();
        while (is_array($this->tzname) && !isset($this->tzname[$propix]) && (0 < count($this->tzname)) && ($propix < end($ak))) {
          $propix++;
        }
        if (!isset($this->tzname[$propix])) {
          unset($this->propix[$propName]);

          return false;
        }

        return ($inclParam) ? $this->tzname[$propix] : $this->tzname[$propix]['value'];
        break;
      case 'TZOFFSETFROM':
        if (!empty($this->tzoffsetfrom['value'])) {
          return ($inclParam) ? $this->tzoffsetfrom : $this->tzoffsetfrom['value'];
        }
        break;
      case 'TZOFFSETTO':
        if (!empty($this->tzoffsetto['value'])) {
          return ($inclParam) ? $this->tzoffsetto : $this->tzoffsetto['value'];
        }
        break;
      case 'TZURL':
        if (!empty($this->tzurl['value'])) {
          return ($inclParam) ? $this->tzurl : $this->tzurl['value'];
        }
        break;
      case 'UID':
        if (in_array($this->objName, array('valarm', 'vtimezone', 'standard', 'daylight'))) {
          return false;
        }
        if (empty($this->uid['value'])) {
          $this->_makeuid();
        }

        return ($inclParam) ? $this->uid : $this->uid['value'];
        break;
      case 'URL':
        if (!empty($this->url['value'])) {
          return ($inclParam) ? $this->url : $this->url['value'];
        }
        break;
      default:
        if ($propName != 'X-PROP') {
          if (!isset($this->xprop[$propName])) {
            return false;
          }

          return ($inclParam) ? array($propName, $this->xprop[$propName])
            : array($propName, $this->xprop[$propName]['value']);
        }
        else {
          if (empty($this->xprop)) {
            return false;
          }
          $xpropno = 0;
          foreach ($this->xprop as $xpropkey => $xpropvalue) {
            if ($propix == $xpropno) {
              return ($inclParam) ? array($xpropkey, $this->xprop[$xpropkey])
                : array($xpropkey, $this->xprop[$xpropkey]['value']);
            }
            else {
              $xpropno++;
            }
          }

          return false; // not found ??
        }
    }

    return false;
  }

  /**
   * returns calendar property unique values for 'CATEGORIES', 'RESOURCES' or 'ATTENDEE' and each number of ocurrence
   *
   * @param string $propName
   * @param array  $output , incremented result array
   *
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.8.8 - 2011-04-13
   */
  function _getProperties($propName, & $output) {
    if (!in_array(strtoupper($propName), array('ATTENDEE', 'CATEGORIES', 'RESOURCES'))) {
      return output;
    }
    while (false !== ($content = $this->getProperty($propName))) {
      if (is_array($content)) {
        foreach ($content as $part) {
          if (false !== strpos($part, ',')) {
            $part = explode(',', $part);
            foreach ($part as $thePart) {
              $thePart = trim($thePart);
              if (!empty($thePart)) {
                if (!isset($output[$thePart])) {
                  $output[$thePart] = 1;
                }
                else {
                  $output[$thePart] += 1;
                }
              }
            }
          }
          else {
            $part = trim($part);
            if (!isset($output[$part])) {
              $output[$part] = 1;
            }
            else {
              $output[$part] += 1;
            }
          }
        }
      }
      elseif (false !== strpos($content, ',')) {
        $content = explode(',', $content);
        foreach ($content as $thePart) {
          $thePart = trim($thePart);
          if (!empty($thePart)) {
            if (!isset($output[$thePart])) {
              $output[$thePart] = 1;
            }
            else {
              $output[$thePart] += 1;
            }
          }
        }
      }
      else {
        $content = trim($content);
        if (!empty($content)) {
          if (!isset($output[$content])) {
            $output[$content] = 1;
          }
          else {
            $output[$content] += 1;
          }
        }
      }
    }
    ksort($output);

    return $output;
  }

  /**
   * general component property setting
   *
   * @param mixed $args variable number of function arguments,
   *                    first argument is ALWAYS component name,
   *                    second ALWAYS component value!
   *
   * @return void
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.5.1 - 2008-11-05
   */
  function setProperty() {
    $numargs = func_num_args();
    if (1 > $numargs) {
      return false;
    }
    $arglist = func_get_args();
    if ($this->_notExistProp($arglist[0])) {
      return false;
    }
    if (!$this->getConfig('allowEmpty') && (!isset($arglist[1]) || empty($arglist[1]))) {
      return false;
    }
    $arglist[0] = strtoupper($arglist[0]);
    for ($argix = $numargs; $argix < 12; $argix++) {
      if (!isset($arglist[$argix])) {
        $arglist[$argix] = null;
      }
    }
    switch ($arglist[0]) {
      case 'ACTION':
        return $this->setAction($arglist[1], $arglist[2]);
      case 'ATTACH':
        return $this->setAttach($arglist[1], $arglist[2], $arglist[3]);
      case 'ATTENDEE':
        return $this->setAttendee($arglist[1], $arglist[2], $arglist[3]);
      case 'CATEGORIES':
        return $this->setCategories($arglist[1], $arglist[2], $arglist[3]);
      case 'CLASS':
        return $this->setClass($arglist[1], $arglist[2]);
      case 'COMMENT':
        return $this->setComment($arglist[1], $arglist[2], $arglist[3]);
      case 'COMPLETED':
        return $this->setCompleted($arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7]);
      case 'CONTACT':
        return $this->setContact($arglist[1], $arglist[2], $arglist[3]);
      case 'CREATED':
        return $this->setCreated($arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7]);
      case 'DESCRIPTION':
        return $this->setDescription($arglist[1], $arglist[2], $arglist[3]);
      case 'DTEND':
        return $this->setDtend($arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7], $arglist[8]);
      case 'DTSTAMP':
        return $this->setDtstamp($arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7]);
      case 'DTSTART':
        return $this->setDtstart($arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7], $arglist[8]);
      case 'DUE':
        return $this->setDue($arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7], $arglist[8]);
      case 'DURATION':
        return $this->setDuration($arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6]);
      case 'EXDATE':
        return $this->setExdate($arglist[1], $arglist[2], $arglist[3]);
      case 'EXRULE':
        return $this->setExrule($arglist[1], $arglist[2], $arglist[3]);
      case 'FREEBUSY':
        return $this->setFreebusy($arglist[1], $arglist[2], $arglist[3], $arglist[4]);
      case 'GEO':
        return $this->setGeo($arglist[1], $arglist[2], $arglist[3]);
      case 'LAST-MODIFIED':
        return $this->setLastModified($arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7]);
      case 'LOCATION':
        return $this->setLocation($arglist[1], $arglist[2]);
      case 'ORGANIZER':
        return $this->setOrganizer($arglist[1], $arglist[2]);
      case 'PERCENT-COMPLETE':
        return $this->setPercentComplete($arglist[1], $arglist[2]);
      case 'PRIORITY':
        return $this->setPriority($arglist[1], $arglist[2]);
      case 'RDATE':
        return $this->setRdate($arglist[1], $arglist[2], $arglist[3]);
      case 'RECURRENCE-ID':
        return $this->setRecurrenceid($arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7], $arglist[8]);
      case 'RELATED-TO':
        return $this->setRelatedTo($arglist[1], $arglist[2], $arglist[3]);
      case 'REPEAT':
        return $this->setRepeat($arglist[1], $arglist[2]);
      case 'REQUEST-STATUS':
        return $this->setRequestStatus($arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5]);
      case 'RESOURCES':
        return $this->setResources($arglist[1], $arglist[2], $arglist[3]);
      case 'RRULE':
        return $this->setRrule($arglist[1], $arglist[2], $arglist[3]);
      case 'SEQUENCE':
        return $this->setSequence($arglist[1], $arglist[2]);
      case 'STATUS':
        return $this->setStatus($arglist[1], $arglist[2]);
      case 'SUMMARY':
        return $this->setSummary($arglist[1], $arglist[2]);
      case 'TRANSP':
        return $this->setTransp($arglist[1], $arglist[2]);
      case 'TRIGGER':
        return $this->setTrigger($arglist[1], $arglist[2], $arglist[3], $arglist[4], $arglist[5], $arglist[6], $arglist[7], $arglist[8], $arglist[9], $arglist[10], $arglist[11]);
      case 'TZID':
        return $this->setTzid($arglist[1], $arglist[2]);
      case 'TZNAME':
        return $this->setTzname($arglist[1], $arglist[2], $arglist[3]);
      case 'TZOFFSETFROM':
        return $this->setTzoffsetfrom($arglist[1], $arglist[2]);
      case 'TZOFFSETTO':
        return $this->setTzoffsetto($arglist[1], $arglist[2]);
      case 'TZURL':
        return $this->setTzurl($arglist[1], $arglist[2]);
      case 'UID':
        return $this->setUid($arglist[1], $arglist[2]);
      case 'URL':
        return $this->setUrl($arglist[1], $arglist[2]);
      default:
        return $this->setXprop($arglist[0], $arglist[1], $arglist[2]);
    }

    return false;
  }
  /*********************************************************************************/
  /**
   * parse component unparsed data into properties
   *
   * @param mixed $unparsedtext , optional, strict rfc2445 formatted, single property string or array of strings
   *
   * @return bool FALSE if error occurs during parsing
   *
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.10.2 - 2011-07-17
   */
  function parse($unparsedtext = null) {
    if (!empty($unparsedtext)) {
      $nl = $this->getConfig('nl');
      if (is_array($unparsedtext)) {
        $unparsedtext = implode('\n' . $nl, $unparsedtext);
      }
      /* fix line folding */
      $eolchars = array("\r\n", "\n\r", "\n", "\r"); // check all line endings
      $EOLmark  = false;
      foreach ($eolchars as $eolchar) {
        if (!$EOLmark && (false !== strpos($unparsedtext, $eolchar))) {
          $unparsedtext = str_replace($eolchar . " ", '', $unparsedtext);
          $unparsedtext = str_replace($eolchar . "\t", '', $unparsedtext);
          if ($eolchar != $nl) {
            $unparsedtext = str_replace($eolchar, $nl, $unparsedtext);
          }
          $EOLmark = true;
        }
      }
      $tmp          = explode($nl, $unparsedtext);
      $unparsedtext = array();
      foreach ($tmp as $tmpr) {
        if (!empty($tmpr)) {
          $unparsedtext[] = $tmpr;
        }
      }
    }
    elseif (!isset($this->unparsed)) {
      $unparsedtext = array();
    }
    else {
      $unparsedtext = $this->unparsed;
    }
    $this->unparsed = array();
    $comp           = &$this;
    $config         = $this->getConfig();
    foreach ($unparsedtext as $line) {
// echo $comp->objName.": $line<br />"; // test ###
      if (in_array(strtoupper(substr($line, 0, 6)), array('END:VA', 'END:DA'))) {
        $this->components[] = $comp->copy();
      }
      elseif ('END:ST' == strtoupper(substr($line, 0, 6))) {
        array_unshift($this->components, $comp->copy());
      }
      elseif ('END:' == strtoupper(substr($line, 0, 4))) {
        break;
      }
      elseif ('BEGIN:VALARM' == strtoupper(substr($line, 0, 12))) {
        $comp = new valarm($config);
      }
      elseif ('BEGIN:STANDARD' == strtoupper(substr($line, 0, 14))) {
        $comp = new vtimezone('standard', $config);
      }
      elseif ('BEGIN:DAYLIGHT' == strtoupper(substr($line, 0, 14))) {
        $comp = new vtimezone('daylight', $config);
      }
      elseif ('BEGIN:' == strtoupper(substr($line, 0, 6))) {
        continue;
      }
      else {
        $comp->unparsed[] = $line;
// echo $comp->objName.": $line<br />\n"; // test ###
      }
    }
    unset($config);
// echo $this->objName.'<br />'.var_export( $this->unparsed, TRUE )."<br />\n"; // test ###
    /* concatenate property values spread over several lines */
    $lastix    = -1;
    $propnames = array('action', 'attach', 'attendee', 'categories', 'comment', 'completed'
    , 'contact', 'class', 'created', 'description', 'dtend', 'dtstart'
    , 'dtstamp', 'due', 'duration', 'exdate', 'exrule', 'freebusy', 'geo'
    , 'last-modified', 'location', 'organizer', 'percent-complete'
    , 'priority', 'rdate', 'recurrence-id', 'related-to', 'repeat'
    , 'request-status', 'resources', 'rrule', 'sequence', 'status'
    , 'summary', 'transp', 'trigger', 'tzid', 'tzname', 'tzoffsetfrom'
    , 'tzoffsetto', 'tzurl', 'uid', 'url', 'x-');
    $proprows  = array();
    foreach ($this->unparsed as $line) {
      $newProp = false;
      foreach ($propnames as $propname) {
        if ($propname == strtolower(substr($line, 0, strlen($propname)))) {
          $newProp = true;
          break;
        }
      }
      if ($newProp) {
        if (-1 < $lastix) {
          $proprows[$lastix] = $proprows[$lastix];
        }
        $newProp = false;
        $lastix++;
        $proprows[$lastix] = $line;
      }
      else {
        $proprows[$lastix] .= '!"#??%&/()=?' . $line;
      }
    }
    /* parse each property 'line' */
    foreach ($proprows as $line) {
      $line = str_replace('!"#??%&/()=? ', '', $line);
      $line = str_replace('!"#??%&/()=?', '', $line);
      if ('\n' == substr($line, -2)) {
        $line = substr($line, 0, strlen($line) - 2);
      }
      /* get propname, (problem with x-properties, otherwise in previous loop) */
      $cix = $propname = null;
      for ($cix = 0, $clen = strlen($line); $cix < $clen; $cix++) {
        if (in_array($line[$cix], array(':', ';'))) {
          break;
        }
        else {
          $propname .= $line[$cix];
        }
      }
      if (('x-' == substr($propname, 0, 2)) || ('X-' == substr($propname, 0, 2))) {
        $propname2 = $propname;
        $propname  = 'X-';
      }
      /* rest of the line is opt.params and value */
      $line = substr($line, $cix);
      /* separate attributes from value */
      $attr   = array();
      $attrix = -1;
      $clen   = strlen($line);
      for ($cix = 0; $cix < $clen; $cix++) {
        if ((':' == $line[$cix]) &&
          ('://' != substr($line, $cix, 3)) &&
          (!in_array(strtolower(substr($line, $cix - 3, 4)), array('fax:', 'cid:', 'sms:', 'tel:', 'urn:'))) &&
          (!in_array(strtolower(substr($line, $cix - 4, 5)), array('crid:', 'news:', 'pres:'))) &&
          ('mailto:' != strtolower(substr($line, $cix - 6, 7)))) {
          $attrEnd = true;
          if (($cix < ($clen - 4)) &&
            ctype_digit(substr($line, $cix + 1, 4))) { // an URI with a (4pos) portnr??
            for ($c2ix = $cix; 3 < $c2ix; $c2ix--) {
              if ('://' == substr($line, $c2ix - 2, 3)) {
                $attrEnd = false;
                break; // an URI with a portnr!!
              }
            }
          }
          if ($attrEnd) {
            $line = substr($line, $cix + 1);
            break;
          }
        }
        if (';' == $line[$cix]) {
          $attr[++$attrix] = null;
        }
        else {
          $attr[$attrix] .= $line[$cix];
        }
      }
      /* make attributes in array format */
      $propattr = array();
      foreach ($attr as $attribute) {
        $attrsplit = explode('=', $attribute, 2);
        if (1 < count($attrsplit)) {
          $propattr[$attrsplit[0]] = $attrsplit[1];
        }
        else {
          $propattr[] = $attribute;
        }
      }
      /* call setProperty( $propname.. . */
      switch (strtoupper($propname)) {
        case 'ATTENDEE':
          foreach ($propattr as $pix => $attr) {
            $attr2 = explode(',', $attr);
            if (1 < count($attr2)) {
              $propattr[$pix] = $attr2;
            }
          }
          $this->setProperty($propname, $line, $propattr);
          break;
        case 'CATEGORIES':
        case 'RESOURCES':
          if (false !== strpos($line, ',')) {
            $content = explode(',', $line);
            $clen    = count($content);
            for ($cix = 0; $cix < $clen; $cix++) {
              if ("\\" == substr($content[$cix], -1)) {
                $content[$cix] .= ',' . $content[$cix + 1];
                unset($content[$cix + 1]);
                $cix++;
              }
            }
            if (1 < count($content)) {
              $content = array_values($content);
              foreach ($content as $cix => $contentPart) {
                $content[$cix] = calendarComponent::_strunrep($contentPart);
              }
              $this->setProperty($propname, $content, $propattr);
              break;
            }
            else {
              $line = reset($content);
            }
          }
        case 'X-':
          $propname = (isset($propname2)) ? $propname2 : $propname;
        case 'COMMENT':
        case 'CONTACT':
        case 'DESCRIPTION':
        case 'LOCATION':
        case 'SUMMARY':
          if (empty($line)) {
            $propattr = null;
          }
          $this->setProperty($propname, calendarComponent::_strunrep($line), $propattr);
          unset($propname2);
          break;
        case 'REQUEST-STATUS':
          $values    = explode(';', $line, 3);
          $values[1] = (!isset($values[1])) ? null : calendarComponent::_strunrep($values[1]);
          $values[2] = (!isset($values[2])) ? null : calendarComponent::_strunrep($values[2]);
          $this->setProperty($propname
            , $values[0]  // statcode
            , $values[1]  // statdesc
            , $values[2]  // extdata
            , $propattr);
          break;
        case 'FREEBUSY':
          $fbtype = (isset($propattr['FBTYPE'])) ? $propattr['FBTYPE'] : ''; // force setting default, if missing
          unset($propattr['FBTYPE']);
          $values = explode(',', $line);
          foreach ($values as $vix => $value) {
            $value2 = explode('/', $value);
            if (1 < count($value2)) {
              $values[$vix] = $value2;
            }
          }
          $this->setProperty($propname, $fbtype, $values, $propattr);
          break;
        case 'GEO':
          $value = explode(';', $line, 2);
          if (2 > count($value)) {
            $value[1] = null;
          }
          $this->setProperty($propname, $value[0], $value[1], $propattr);
          break;
        case 'EXDATE':
          $values = (!empty($line)) ? explode(',', $line) : null;
          $this->setProperty($propname, $values, $propattr);
          break;
        case 'RDATE':
          if (empty($line)) {
            $this->setProperty($propname, $line, $propattr);
            break;
          }
          $values = explode(',', $line);
          foreach ($values as $vix => $value) {
            $value2 = explode('/', $value);
            if (1 < count($value2)) {
              $values[$vix] = $value2;
            }
          }
          $this->setProperty($propname, $values, $propattr);
          break;
        case 'EXRULE':
        case 'RRULE':
          $values = explode(';', $line);
          $recur  = array();
          foreach ($values as $value2) {
            if (empty($value2)) {
              continue;
            } // ;-char in ending position ???
            $value3    = explode('=', $value2, 2);
            $rulelabel = strtoupper($value3[0]);
            switch ($rulelabel) {
              case 'BYDAY':
              {
                $value4 = explode(',', $value3[1]);
                if (1 < count($value4)) {
                  foreach ($value4 as $v5ix => $value5) {
                    $value6 = array();
                    $dayno  = $dayname = null;
                    $value5 = trim((string)$value5);
                    if ((ctype_alpha(substr($value5, -1))) &&
                      (ctype_alpha(substr($value5, -2, 1)))) {
                      $dayname = substr($value5, -2, 2);
                      if (2 < strlen($value5)) {
                        $dayno = substr($value5, 0, (strlen($value5) - 2));
                      }
                    }
                    if ($dayno) {
                      $value6[] = $dayno;
                    }
                    if ($dayname) {
                      $value6['DAY'] = $dayname;
                    }
                    $value4[$v5ix] = $value6;
                  }
                }
                else {
                  $value4 = array();
                  $dayno  = $dayname = null;
                  $value5 = trim((string)$value3[1]);
                  if ((ctype_alpha(substr($value5, -1))) &&
                    (ctype_alpha(substr($value5, -2, 1)))) {
                    $dayname = substr($value5, -2, 2);
                    if (2 < strlen($value5)) {
                      $dayno = substr($value5, 0, (strlen($value5) - 2));
                    }
                  }
                  if ($dayno) {
                    $value4[] = $dayno;
                  }
                  if ($dayname) {
                    $value4['DAY'] = $dayname;
                  }
                }
                $recur[$rulelabel] = $value4;
                break;
              }
              default:
              {
                $value4 = explode(',', $value3[1]);
                if (1 < count($value4)) {
                  $value3[1] = $value4;
                }
                $recur[$rulelabel] = $value3[1];
                break;
              }
            } // end - switch $rulelabel
          } // end - foreach( $values.. .
          $this->setProperty($propname, $recur, $propattr);
          break;
        case 'ACTION':
        case 'CLASSIFICATION':
        case 'STATUS':
        case 'TRANSP':
        case 'UID':
        case 'TZID':
        case 'RELATED-TO':
        case 'TZNAME':
          $line = calendarComponent::_strunrep($line);
        default:
          $this->setProperty($propname, $line, $propattr);
          break;
      } // end  switch( $propname.. .
    } // end - foreach( $proprows.. .
    unset($unparsedtext, $this->unparsed, $proprows);
    if (isset($this->components) && is_array($this->components) && (0 < count($this->components))) {
      $ckeys = array_keys($this->components);
      foreach ($ckeys as $ckey) {
        if (!empty($this->components[$ckey]) && !empty($this->components[$ckey]->unparsed)) {
          $this->components[$ckey]->parse();
        }
      }
    }

    return true;
  }
  /*********************************************************************************/
  /*********************************************************************************/
  /**
   * return a copy of this component
   *
   * @return object
   * @since  2.8.8 - 2011-03-15
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function copy() {
    $serialized_contents = serialize($this);
    $copy                = unserialize($serialized_contents);

    return $copy;
  }
  /*********************************************************************************/
  /*********************************************************************************/
  /**
   * delete calendar subcomponent from component container
   *
   * @param mixed $arg1 ordno / component type / component uid
   * @param mixed $arg2 optional, ordno if arg1 = component type
   *
   * @return void
   * @since  2.8.8 - 2011-03-15
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function deleteComponent($arg1, $arg2 = false) {
    if (!isset($this->components)) {
      return false;
    }
    $argType = $index = null;
    if (ctype_digit((string)$arg1)) {
      $argType = 'INDEX';
      $index   = (int)$arg1 - 1;
    }
    elseif ((strlen($arg1) <= strlen('vfreebusy')) && (false === strpos($arg1, '@'))) {
      $argType = strtolower($arg1);
      $index   = (!empty($arg2) && ctype_digit((string)$arg2)) ? (( int )$arg2 - 1) : 0;
    }
    $cix2dC = 0;
    foreach ($this->components as $cix => $component) {
      if (empty($component)) {
        continue;
      }
      if (('INDEX' == $argType) && ($index == $cix)) {
        unset($this->components[$cix]);

        return true;
      }
      elseif ($argType == $component->objName) {
        if ($index == $cix2dC) {
          unset($this->components[$cix]);

          return true;
        }
        $cix2dC++;
      }
      elseif (!$argType && ($arg1 == $component->getProperty('uid'))) {
        unset($this->components[$cix]);

        return true;
      }
    }

    return false;
  }

  /**
   * get calendar component subcomponent from component container
   *
   * @param mixed $arg1 optional, ordno/component type/ component uid
   * @param mixed $arg2 optional, ordno if arg1 = component type
   *
   * @return object
   * @since  2.8.8 - 2011-03-15
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function getComponent($arg1 = false, $arg2 = false) {
    if (!isset($this->components)) {
      return false;
    }
    $index = $argType = null;
    if (!$arg1) {
      $argType = 'INDEX';
      $index   = $this->compix['INDEX'] =
        (isset($this->compix['INDEX'])) ? $this->compix['INDEX'] + 1 : 1;
    }
    elseif (ctype_digit((string)$arg1)) {
      $argType = 'INDEX';
      $index   = (int)$arg1;
      unset($this->compix);
    }
    elseif ((strlen($arg1) <= strlen('vfreebusy')) && (false === strpos($arg1, '@'))) {
      unset($this->compix['INDEX']);
      $argType = strtolower($arg1);
      if (!$arg2) {
        $index = $this->compix[$argType] = (isset($this->compix[$argType])) ? $this->compix[$argType] + 1 : 1;
      }
      else {
        $index = (int)$arg2;
      }
    }
    $index -= 1;
    $ckeys = array_keys($this->components);
    if (!empty($index) && ($index > end($ckeys))) {
      return false;
    }
    $cix2gC = 0;
    foreach ($this->components as $cix => $component) {
      if (empty($component)) {
        continue;
      }
      if (('INDEX' == $argType) && ($index == $cix)) {
        return $component->copy();
      }
      elseif ($argType == $component->objName) {
        if ($index == $cix2gC) {
          return $component->copy();
        }
        $cix2gC++;
      }
      elseif (!$argType && ($arg1 == $component->getProperty('uid'))) {
        return $component->copy();
      }
    }
    /* not found.. . */
    unset($this->compix);

    return false;
  }

  /**
   * add calendar component as subcomponent to container for subcomponents
   *
   * @param object $component calendar component
   *
   * @return void
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  1.x.x - 2007-04-24
   */
  function addSubComponent($component) {
    $this->setComponent($component);
  }

  /**
   * create new calendar component subcomponent, already included within component
   *
   * @param string $compType subcomponent type
   *
   * @return object (reference)
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.6.33 - 2011-01-03
   */
  function & newComponent($compType) {
    $config = $this->getConfig();
    $keys   = array_keys($this->components);
    $ix     = end($keys) + 1;
    switch (strtoupper($compType)) {
      case 'ALARM':
      case 'VALARM':
        $this->components[$ix] = new valarm($config);
        break;
      case 'STANDARD':
        array_unshift($this->components, new vtimezone('STANDARD', $config));
        $ix = 0;
        break;
      case 'DAYLIGHT':
        $this->components[$ix] = new vtimezone('DAYLIGHT', $config);
        break;
      default:
        return false;
    }

    return $this->components[$ix];
  }

  /**
   * add calendar component as subcomponent to container for subcomponents
   *
   * @param object $component calendar component
   * @param mixed  $arg1      optional, ordno/component type/ component uid
   * @param mixed  $arg2      optional, ordno if arg1 = component type
   *
   * @return bool
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.8.8 - 2011-03-15
   */
  function setComponent($component, $arg1 = false, $arg2 = false) {
    if (!isset($this->components)) {
      return false;
    }
    $component->setConfig($this->getConfig(), false, true);
    if (!in_array($component->objName, array('valarm', 'vtimezone', 'standard', 'daylight'))) {
      /* make sure dtstamp and uid is set */
//$dummy = $component->getProperty( 'dtstamp' );
      $dummy = $component->getProperty('uid');
    }
    if (!$arg1) { // plain insert, last in chain
      $this->components[] = $component->copy();

      return true;
    }
    $argType = $index = null;
    if (ctype_digit((string)$arg1)) { // index insert/replace
      $argType = 'INDEX';
      $index   = (int)$arg1 - 1;
    }
    elseif (in_array(strtolower($arg1), array('vevent', 'vtodo', 'vjournal', 'vfreebusy', 'valarm', 'vtimezone'))) {
      $argType = strtolower($arg1);
      $index   = (ctype_digit((string)$arg2)) ? ((int)$arg2) - 1 : 0;
    }
// else if arg1 is set, arg1 must be an UID
    $cix2sC = 0;
    foreach ($this->components as $cix => $component2) {
      if (empty($component2)) {
        continue;
      }
      if (('INDEX' == $argType) && ($index == $cix)) { // index insert/replace
        $this->components[$cix] = $component->copy();

        return true;
      }
      elseif ($argType == $component2->objName) { // component Type index insert/replace
        if ($index == $cix2sC) {
          $this->components[$cix] = $component->copy();

          return true;
        }
        $cix2sC++;
      }
      elseif (!$argType && ($arg1 == $component2->getProperty('uid'))) { // UID insert/replace
        $this->components[$cix] = $component->copy();

        return true;
      }
    }
    /* arg1=index and not found.. . insert at index .. .*/
    if ('INDEX' == $argType) {
      $this->components[$index] = $component->copy();
      ksort($this->components, SORT_NUMERIC);
    }
    else    /* not found.. . insert last in chain anyway .. .*/ {
      $this->components[] = $component->copy();
    }

    return true;
  }

  /**
   * creates formatted output for subcomponents
   *
   * @return string
   * @since  2.6.27 - 2010-12-12
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   */
  function createSubComponent() {
    $output = null;
    foreach ($this->components as $component) {
      if (empty($component)) {
        continue;
      }
      $component->setConfig($this->getConfig(), false, true);
      $output .= $component->createComponent($this->xcaldecl);
    }

    return $output;
  }
  /********************************************************************************/
  /**
   * break lines at pos 75
   *
   * Lines of text SHOULD NOT be longer than 75 octets, excluding the line
   * break. Long content lines SHOULD be split into a multiple line
   * representations using a line "folding" technique. That is, a long
   * line can be split between any two characters by inserting a CRLF
   * immediately followed by a single linear white space character (i.e.,
   * SPACE, US-ASCII decimal 32 or HTAB, US-ASCII decimal 9). Any sequence
   * of CRLF followed immediately by a single linear white space character
   * is ignored (i.e., removed) when processing the content type.
   *
   * Edited 2007-08-26 by Anders Litzell, anders@litzell.se to fix bug where
   * the reserved expression "\n" in the arg $string could be broken up by the
   * folding of lines, causing ambiguity in the return string.
   * Fix uses var $breakAtChar=75 and breaks the line at $breakAtChar-1 if need be.
   *
   * @param string $value
   *
   * @return string
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.10.11 - 2011-09-01
   */
  function _size75($string) {
    $tmp        = $string;
    $string     = '';
    $eolcharlen = strlen('\n');
    /* if PHP is config with conf overload.. . */
    if (defined('MB_OVERLOAD_STRING')) {
      $strlen = mb_strlen($tmp);
      while ($strlen > 75) {
        if ('\n' == mb_substr($tmp, 75, $eolcharlen)) {
          $breakAtChar = 74;
        }
        else {
          $breakAtChar = 75;
        }
        $string .= mb_substr($tmp, 0, $breakAtChar);
        if ($this->nl != mb_substr($string, (0 - mb_strlen($this->nl)))) {
          $string .= $this->nl;
        }
        $tmp = mb_substr($tmp, $breakAtChar);
        if (!empty($tmp)) {
          $tmp = ' ' . $tmp;
        }
        $strlen = mb_strlen($tmp);
      } // end while
      if (0 < $strlen) {
        $string .= $tmp; // the rest
        if ($this->nl != mb_substr($string, (0 - mb_strlen($this->nl)))) {
          $string .= $this->nl;
        }
      }

      return $string;
    }
    /* if PHP is not config with  mb_string.. . */
    while (true) {
      $bytecnt = strlen($tmp);
      $charCnt = $ix = 0;
      for ($ix = 0; $ix < $bytecnt; $ix++) {
        if ((73 < $charCnt) && ('\n' == substr($tmp, $ix, $eolcharlen))) {
          break;
        }                                    // break before '\n'
        elseif (74 < $charCnt) {
          if ('\n' == substr($tmp, $ix, $eolcharlen)) {
            $ix -= 1;
          }                               // don't break inside '\n'
          break;                                    // always break while-loop here
        }
        else {
          $byte = ord($tmp[$ix]);
          if ($byte <= 127) {                       // add a one byte character
            $string  .= substr($tmp, $ix, 1);
            $charCnt += 1;
          }
          else {
            if ($byte >= 194 && $byte <= 223) {  // start byte in two byte character
              $string  .= substr($tmp, $ix, 2);      // add a two bytes character
              $charCnt += 1;
            }
            else {
              if ($byte >= 224 && $byte <= 239) {  // start byte in three bytes character
                $string  .= substr($tmp, $ix, 3);      // add a three bytes character
                $charCnt += 1;
              }
              else {
                if ($byte >= 240 && $byte <= 244) {  // start byte in four bytes character
                  $string  .= substr($tmp, $ix, 4);      // add a four bytes character
                  $charCnt += 1;
                }
              }
            }
          }
        }
      } // end for
      if ($this->nl != substr($string, (0 - strlen($this->nl)))) {
        $string .= $this->nl;
      }
      $tmp = substr($tmp, $ix);
      if (empty($tmp)) {
        break;
      } // while-loop breakes here
      else {
        $tmp = ' ' . $tmp;
      }
    } // end while

    return $string;
  }

  /**
   * special characters management output
   *
   * @param string $string
   *
   * @return string
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.6.15 - 2010-09-24
   */
  function _strrep($string) {
    switch ($this->format) {
      case 'xcal':
        $string = str_replace('\n', $this->nl, $string);
        $string = htmlspecialchars(strip_tags(stripslashes(urldecode($string))));
        break;
      default:
        $pos       = 0;
        $specChars = array('n', 'N', 'r', ',', ';');
        while ($pos <= strlen($string)) {
          $pos = strpos($string, "\\", $pos);
          if (false === $pos) {
            break;
          }
          if (!in_array(substr($string, $pos, 1), $specChars)) {
            $string = substr($string, 0, $pos) . "\\" . substr($string, ($pos + 1));
            $pos    += 1;
          }
          $pos += 1;
        }
        if (false !== strpos($string, '"')) {
          $string = str_replace('"', "'", $string);
        }
        if (false !== strpos($string, ',')) {
          $string = str_replace(',', '\,', $string);
        }
        if (false !== strpos($string, ';')) {
          $string = str_replace(';', '\;', $string);
        }

        if (false !== strpos($string, "\r\n")) {
          $string = str_replace("\r\n", '\n', $string);
        }
        elseif (false !== strpos($string, "\r")) {
          $string = str_replace("\r", '\n', $string);
        }

        elseif (false !== strpos($string, "\n")) {
          $string = str_replace("\n", '\n', $string);
        }

        if (false !== strpos($string, '\N')) {
          $string = str_replace('\N', '\n', $string);
        }
//        if( FALSE !== strpos( $string, $this->nl ))
        $string = str_replace($this->nl, '\n', $string);
        break;
    }

    return $string;
  }

  /**
   * special characters management input (from iCal file)
   *
   * @param string $string
   *
   * @return string
   * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
   * @since  2.6.22 - 2010-10-17
   */
  static function _strunrep($string) {
    $string = str_replace('\\\\', '\\', $string);
    $string = str_replace('\,', ',', $string);
    $string = str_replace('\;', ';', $string);

//    $string = str_replace( '\n',  $this->nl, $string); // ??
    return $string;
  }
}
