<?php

/**
 * @file
 * Contains \Drupal\filter\Plugin\Filter\LyricSheetChords.
 */

namespace Drupal\filter\Plugin\Filter;

use Drupsl\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;



/**
 * Provides a filter to use this module.
 *
 * @Filter(
 *   id = "lyric_sheet_chords",
 *   title = @Translation("Format lyric sheet chords"),
 *   description = @Translation("Substitutes chord tags such as [C] with a nicely formatted chord marking."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "lyric_sheet_chords_remove" = TRUE
 *   },
 *   weight = 100
 * )
 */
class LyricSheetChords extends FilterBase {
  
  public function settingsForm(array $form, FormStateInterface $form_state) {
 	$this->settings += $defaults;
    $form['lyric_sheet_chords_remove'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Insert link to remove chords'),
      '#description' => $this->t('A javascript "remove chords" button is useful for people who want to copy only the text.'),
      '#default_value' => $this->settings['lyric_sheet_chords_remove'],
    );
    return $form;
  }

  public function tips($long = FALSE) {
    if ($long) {
      return t('Always begin the chord with a capital letter A-G.  Use # for sharp and b for flat.  If you have a bass note, put it at the end of the chord after a slash.  The following are a few chord types that are recognized: <ul>
        <li>[Am7] = A minor 7</li>
        <li>[Cmaj7] = C major 7</li>
        <li>[F#13] = F sharp 13</li>
        <li>[Dsus4] = D suspended 4</li>
        <li>[C/G] = C over G (G as bass)</li>
        <li>[Cmaj/min7] = C major minor 7</li>
        <li>[GMaj7b13] = G major 7 flat 13</li>
        </ul>');
    }
    else {
      return t('To get nicely formatted chords, enclose the chord in brackets, like: [C], [F], [Amin7], [Dsus4], etc.');
    }   
  }

  public function process($text, $langcode) {
    $style = $link = '';
    $matches = array();
    if (preg_match_all("|\[([A-G][-\+MADSmajinorsudimg /#b1-9]*/*[A-G1-9#b]*)\]|", $text, $matches)) {
      if ($this->settings['lyric_sheet_chords_remove']) {
        $link = "\n" . '<a href="#" class="remove-chords">' . t('remove chords') . '</a>';
      }
      foreach ($matches[0] as $i => $match) {
        $chords[$match] = $matches[1][$i];
      }
      foreach ($chords as $markup => $chord) {
        $class = str_replace('-', 'minus', $chord);
        $class = str_replace('plus', 'plus', $class);
        $class = str_replace('#', 'sharp', $class);
        $class = str_replace('/', 'over', $class);
        $class = str_replace(' ', '', $class);
        $text = str_replace($markup, '<span class="chord"><span class="chord-text ' . $class . '"></span></span>', $text);
        $style .= ".chord-text.$class:before { content: \"$chord\" }\n";
      }
      return "<style>\n$style</style>$link\n$text";
    }
    else {
      return new FilterProcessResult($text);
    }
  }

}

/**
 * Implements hook_entity_display_build_alter().
 *
 * Very often, chords are inserted in the middle of words; this function should
 * remove all such chords for the purposes of search.
 */
function lyric_sheet_chords_entity_display_build_alter(&$build, $context) {
  if ($context['view_mode'] == 'search_index' || $context['view_mode'] == 'search_result') {
    foreach (\Drupal\Core\Render\Element::children($build) as $field) {
      if (!empty($build[$field][0]['#markup'])) {
        foreach (\Drupal\Core\Render\Element::children($build[$field]) as $index) {
          $build[$field][$index]['#markup'] = preg_replace("|<span class=\"chord\"><span[^<]+</span></span>|", '', $build[$field][$index]['#markup']);
          $build[$field][$index]['#markup'] = preg_replace("|\.chord-text[^}]+}\n|", '', $build[$field][$index]['#markup']);
          $build[$field][$index]['#markup'] = str_replace("<style>\n<!--/*--><![CDATA[/* ><!--*/\n\n\n/*--><!]]>*/\n</style>", '', $build[$field][$index]['#markup']);
          $build[$field][$index]['#markup'] = preg_replace('|<a href="#" class="remove-chords">[^\<]*</a>|', '', $build[$field][$index]['#markup']);
        }
      }
    }
  }
}
