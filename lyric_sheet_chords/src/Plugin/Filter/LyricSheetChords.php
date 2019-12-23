<?php

/**
 * @file
 * Contains \Drupal\lyric_sheet_chords\Plugin\Filter\LyricSheetChords.
 */

namespace Drupal\lyric_sheet_chords\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;

/**
 * Provides a filter to use this module.
 *
 * @Filter(
 *   id = "lyric_sheet_chords",
 *   title = @Translation("Format lyric sheet chords"),
 *   description = @Translation("Substitutes chord tags such as [C] with a nicely formatted chord marking."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "lyric_sheet_chords_remove" = TRUE
 *   },
 *   weight = 100
 * )
 */
class LyricSheetChords extends FilterBase {
  
  public function settingsForm(array $form, FormStateInterface $form_state) {    
    $form['lyric_sheet_chords_remove'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Insert link to remove chords'),
      '#description' => $this->t('A javascript "remove chords" button is useful for people who want to copy only the text.'),
      '#default_value' => $this->settings['lyric_sheet_chords_remove'],
    ];
    return $form;
  }

  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('Always begin the chord with a capital letter A-G.  Use # for sharp and b for flat.  If you have a bass note, put it at the end of the chord after a slash.  The following are a few chord types that are recognized: <ul>
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
      return $this->t('To get nicely formatted chords, enclose the chord in brackets, like: [C], [F], [Amin7], [Dsus4], etc.');
    }   
  }

  public function process($text, $langcode) {   
    $style = $link = '';
    $matches = [];
    $optional = "<style>\n$style</style>$link\n$text";
    if (preg_match_all("|\[([A-G][-\+MADSmajinorsudimg /#b1-9]*/*[A-G1-9#b]*)\]|", $text, $matches)) {
      if ($this->settings['lyric_sheet_chords_remove']) {
        $link = "\n" . '<a href="#" class="remove-chords">' . $this->t('remove chords') . '</a>';
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
      return new FilterProcessResult($optional);
    }
    else {
      return new FilterProcessResult($text);
    }
  }

}
