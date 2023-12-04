<?php
/**
 * nv_theme_dictionary_main()
 *
 * @param string $search_word
 * @param array $found_words
 * @param bool $is_submit
 * @return string
 */
function nv_theme_dictionary_main($search_word, $found_words, $is_submit)
{
    global $module_info, $module_file;

    $xtpl = new XTemplate('main.tpl', NV_ROOTDIR . '/themes/' . $module_info['template'] . '/modules/' . $module_file);
    $xtpl->assign('SEARCH_WORD', htmlspecialchars($search_word, ENT_QUOTES));

    if ($is_submit && !empty($found_words)) {
        foreach ($found_words as $word) {
            $audio_url = NV_BASE_SITEURL . 'modules/' . $module_file . '/data/' . basename($word['audioPath']);
            $word_data = [
                'words' => $word['words'],
                'spelling' => $word['spelling'],
                'translation' => $word['translation'],
                'loaitu' => $word['loaitu'],
                'description'=>$word['description'],
                'audioPath' => $audio_url,
            ];
            $xtpl->assign('WORD', $word_data);

            if (!empty($word['example'])) {
                foreach ($word['example'] as $example_data) {
                    $xtpl->assign('EXAMPLE', $example_data['example']);
                    $xtpl->assign('EXAMPLE_TRANSLATION', $example_data['example_translation']);
                    $xtpl->parse('main.result.word.example');
                }
            }

            $xtpl->parse('main.result.word');
        }

        $xtpl->parse('main.result');
    } elseif ($is_submit) {
        $xtpl->parse('main.no_result');
    }

    $xtpl->parse('main');
    return $xtpl->text('main');
}



