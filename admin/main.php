<?php
$page_title = $lang_module['main'];
$data_folder = NV_ROOTDIR . '/modules/' . $module_file . '/data';
if (!file_exists($data_folder)) {
    @mkdir($data_folder, 0755);
}
function writeCache($folder, $file, $data) {
    $file_path = $folder . '/' . $file;
    return file_put_contents($file_path, serialize($data)) !== false;
}
function readCache($folder, $file) {
    $file_path = $folder . '/' . $file;
    if (file_exists($file_path)) {
        return unserialize(file_get_contents($file_path));
    }
    return false;
}
$search_word = isset($_GET['search_word']) ? $_GET['search_word'] : '';
$found_word_id = null;
$word = '';
$translation = '';
$spelling = '';
$audioPath = '';
$loaitu = '';
$description='';
$examples = [];
$translations = [];
if ($search_word) {
    $dictionary_list = readCache($data_folder, 'dictionary_list.txt') ?: [];
    $example_list = readCache($data_folder, 'example_list.txt') ?: [];
    foreach ($dictionary_list as $id => $entry) {
        if ($entry['words'] === $search_word) {
            $found_word_id = $id;
            $word = $entry['words'];
            $translation = $entry['translation'];
            $description=$entry['description'];
            $spelling = $entry['spelling'];
            $audioPath = $entry['audioPath'] ?? '';
            $loaitu = $entry['loaitu'] ?? '';
            if (isset($example_list[$found_word_id])) {
                foreach ($example_list[$found_word_id] as $example_data) {
                    $examples[] = $example_data['example'];
                    $translations[] = $example_data['example_translation'];
                }
            }
            break;
        }
    }
}
$error = '';
$success = '';
if ($nv_Request->isset_request('submit', 'post')) {
    $word = $nv_Request->get_title('words', 'post', '');
    $translation = $nv_Request->get_title('translation', 'post', '');
    $description = $nv_Request->get_title('description', 'post', '');
    $spelling = $nv_Request->get_title('spelling', 'post', '');
    $loaitu = $nv_Request->get_title('loaitu', 'post', '');
    $examples_post = $nv_Request->get_array('example', 'post', '');
    $translations_post = $nv_Request->get_array('example_translation', 'post', '');

    if (empty($word) || empty($translation) || empty($spelling) || empty($loaitu)) {
        $error = $lang_module['error_required_translate'];
    } else {
        if (isset($_FILES['audioFile']) && $_FILES['audioFile']['error'] == 0) {
            $audio_file = $_FILES['audioFile'];
            $audio_file_name = time() . '_' . $audio_file['name'];
            $audio_file_tmp_path = $audio_file['tmp_name'];
            $audio_file_new_path = $data_folder . '/' . $audio_file_name;

            if (in_array(strtolower(pathinfo($audio_file_name, PATHINFO_EXTENSION)), ['mp3', 'wav', 'ogg'])) {
                if (move_uploaded_file($audio_file_tmp_path, $audio_file_new_path)) {
                    $audioPath = $audio_file_new_path;
                } else {
                    $error = "Không thể tải lên tệp âm thanh.";
                }
            } else {
                $error = "Định dạng tệp không được hỗ trợ.";
            }
        }

        $dictionary_list = readCache($data_folder, 'dictionary_list.txt') ?: [];
        $dictionary_id = $found_word_id !== null ? $found_word_id : count($dictionary_list) + 1;
        $dictionary_list[$dictionary_id] = [
            'id' => $dictionary_id,
            'words' => $word,
            'translation' => $translation,
            'description'=>$description,
            'spelling' => $spelling,
            'audioPath' => $audioPath,
            'loaitu' => $loaitu
        ];
        $result=writeCache($data_folder, 'dictionary_list.txt', $dictionary_list);

        $example_list = readCache($data_folder, 'example_list.txt') ?: [];
        $example_list[$dictionary_id] = [];
        foreach ($examples_post as $index => $example) {
            if (isset($translations_post[$index])) {
                $example_list[$dictionary_id][] = [
                    'example' => $example,
                    'example_translation' => $translations_post[$index]
                ];
            }
        }
        $result=writeCache($data_folder, 'example_list.txt', $example_list);
        $message = $result ? "Dữ liệu đã được lưu thành công!" : "Có lỗi xảy ra khi lưu dữ liệu!";
        $type = $result ? "success" : "error";
    }
}

$xtpl = new XTemplate('main.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('NV_LANG_VARIABLE', NV_LANG_VARIABLE);
$xtpl->assign('NV_LANG_DATA', NV_LANG_DATA);
$xtpl->assign('NV_BASE_ADMINURL', NV_BASE_ADMINURL);
$xtpl->assign('NV_NAME_VARIABLE', NV_NAME_VARIABLE);
$xtpl->assign('NV_OP_VARIABLE', NV_OP_VARIABLE);
$xtpl->assign('MODULE_NAME', $module_name);
$xtpl->assign('OP', $op);
$xtpl->assign('WORD', $word);
$xtpl->assign('ALERT_MESSAGE', $message);
$xtpl->assign('ALERT_TYPE', $type);
$xtpl->assign('TRANSLATION', $translation);
$xtpl->assign('DESCRIPTION',$description);
$xtpl->assign('SPELLING', $spelling);
$xtpl->assign('LOAITU', $loaitu);
$loai_tus = [
    'Tính từ' => 'Tính từ (Adjective)',
    'Động từ' => 'Động từ (Verb)',
    'Danh từ' => 'Danh từ (Noun)',
    'Trạng từ' => 'Trạng từ (Adverb)',
    'Đại từ' => 'Đại từ (Pronoun)',
    'Liên từ' => 'Liên từ (Conjunction)',
    'Giới từ' => 'Giới từ (Preposition)'
];
foreach ($loai_tus as $key => $text) {
    $selected = ($loaitu == $key) ? 'selected' : '';
    $xtpl->assign('OPTION_VALUE', $key);
    $xtpl->assign('OPTION_TEXT', $text);
    $xtpl->assign('SELECTED', $selected);
    $xtpl->parse('main.loaitu_option');
}
if (!empty($examples) && !empty($translations)) {
    foreach ($examples as $index => $example) {
        $xtpl->assign('EXAMPLE_VALUE', $example);
        $xtpl->assign('TRANSLATION_VALUE', $translations[$index]);
        $xtpl->assign('EXAMPLE_INDEX', $index);
        $xtpl->parse('main.existing_examples');
    }
}
if (!empty($found_word)) {
    $xtpl->parse('main.edit_form');
} else if ($search_word) {
    $xtpl->assign('NOT_FOUND', 'Không tìm thấy từ: ' . $search_word);
    $xtpl->parse('main.not_found');
}
if (!empty($audioPath)) {
    $audio_url = NV_BASE_SITEURL . 'modules/' . $module_file . '/data/' . basename($audioPath);
    $xtpl->assign('AUDIO_PATH', $audio_url);
    $xtpl->parse('main.has_audio');
}
$xtpl->parse('main');
$contents = $xtpl->text('main');
include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
