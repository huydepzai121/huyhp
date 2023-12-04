<?php
/**
 * NukeViet - Website engine
 *
 * @package     NVSystem
 * @subpackage  Dictionary
 * @link        http://nukeviet.vn
 * @copyright   (c) 2004-2022, http://nukeviet.vn
 * @license     GNU/GPL version 2 or any later version
 * @version     4.0.29
 */

if (!defined('NV_IS_MOD_DICTIONARY')) {
    die('Stop!!!');
}

// Hàm đọc cache
function readCache($folder, $file) {
    $file_path = $folder . '/' . $file;
    if (file_exists($file_path)) {
        return unserialize(file_get_contents($file_path));
    }
    return [];
}

$data_folder = NV_ROOTDIR . '/modules/' . $module_file . '/data';

$search_word = $nv_Request->get_title('search_word', 'post,get', '');
$search_type = $nv_Request->get_int('rdb', 'post', 0); // 0 for exact phrase, 1 for any word
$found_words = [];
$is_submit = !empty($search_word);

if ($is_submit) {
    $dictionary_list = readCache($data_folder, 'dictionary_list.txt') ?: [];
    $example_list = readCache($data_folder, 'example_list.txt') ?: [];

    foreach ($dictionary_list as $word_id => $word_arr) {
        $match_found = $search_type == 0 ? strcasecmp($word_arr['words'], $search_word) === 0 : false;
        if ($search_type == 1) {
            $search_words = explode(' ', $search_word);
            foreach ($search_words as $word) {
                if (stripos($word_arr['words'], $word) !== false) {
                    $match_found = true;
                    break;
                }
            }
        }

        if ($match_found) {
            // Kiểm tra và thu thập tất cả các ví dụ cho từ này
            if (isset($example_list[$word_id])) {
                $word_arr['example'] = $example_list[$word_id];
            } else {
                $word_arr['example'] = [];
            }
            $found_words[] = $word_arr;
            if ($search_type == 0) break; // Chỉ thêm từ đầu tiên phù hợp cho tìm kiếm cụm từ chính xác
        }
    }
}


$contents = nv_theme_dictionary_main($search_word, $found_words, $is_submit);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
