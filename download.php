<?php
/*
Copyright 2019 whatever127

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

$updateId = isset($_GET['id']) ? $_GET['id'] : null;
$usePack = isset($_GET['pack']) ? $_GET['pack'] : 0;
$desiredEdition = isset($_GET['edition']) ? $_GET['edition'] : 0;

require_once 'api/get.php';
require_once 'api/listlangs.php';
require_once 'api/listeditions.php';
require_once 'shared/style.php';

if(!$updateId) {
    fancyError('UNSPECIFIED_UPDATE', 'downloads');
    die();
}

if(!checkUpdateIdValidity($updateId)) {
    fancyError('INCORRECT_ID', 'downloads');
    die();
}

$url = "./get.php?id=$updateId&pack=$usePack&edition=$desiredEdition";
if(!$usePack && !$desiredEdition) {
    $url = "./findfiles.php?id=$updateId";
}

if(!$usePack || $desiredEdition == 'updateOnly' || $desiredEdition == 'wubFile') {
    header("Location: $url");
    echo "<h1>Moved to <a href=\"$url\">here</a>.";
    die();
}

$files = uupGetFiles($updateId, $usePack, $desiredEdition, 2);
if(isset($files['error'])) {
    fancyError($files['error'], 'downloads');
    die();
}

$updates = uupGetFiles($updateId, 0, 'updateOnly', 2);
if(isset($updates['error'])) {
    $hasUpdates = 0;
} else {
    $hasUpdates = 1;
}

$build = explode('.', $files['build']);
$build = @$build[0];
if($build < 17107) {
    $disableVE = 1;
} else {
    $disableVE = 0;
}

$updateTitle = "{$files['updateName']} {$files['arch']}";
$updateArch = $files['arch'];
$files = $files['files'];

$totalSize = 0;
foreach($files as $file) {
    $totalSize += $file['size'];
}

$prefixes = array('', 'Ki', 'Mi', 'Gi', 'Ti', 'Pi', 'Ei', 'Zi', 'Yi');
foreach($prefixes as $prefix) {
    if($totalSize < 1024) break;
    $totalSize = $totalSize / 1024;
}
$totalSize = round($totalSize, 2);
$totalSize = "$totalSize {$prefix}B";

if($usePack) {
    if(isset($s['lang_'.strtolower($usePack)])) {
        $selectedLangName = $s['lang_'.strtolower($usePack)];
    } else {
        $langs = uupListLangs($updateId);
        $langs = $langs['langFancyNames'];

        $selectedLangName = $langs[strtolower($usePack)];
    }
} else {
    $selectedLangName = $s['allLanguages'];
}

if($usePack && $desiredEdition) {
    $editions = uupListEditions($usePack, $updateId);
    $editions = $editions['editionFancyNames'];

    $selectedEditionName = $editions[strtoupper($desiredEdition)];
} else {
    $selectedEditionName = $s['allEditions'];
}

$filesKeys = array_keys($files);
$virtualEditions = array();

if(preg_grep('/^Core_.*\.esd/i', $filesKeys)) {
    $virtualEditions['CoreSingleLanguage'] = 'Home Single Language';
}

if(preg_grep('/^Professional_.*\.esd/i', $filesKeys)) {
    $virtualEditions['ProfessionalWorkstation'] = 'Pro for Workstations';
    $virtualEditions['ProfessionalEducation'] = 'Pro Education';
    $virtualEditions['Education'] = 'Education';
    $virtualEditions['Enterprise'] = 'Enterprise';
    $virtualEditions['ServerRdsh'] = 'Enterprise for Virtual Desktops';

    if($build >= 18277) {
        $virtualEditions['IoTEnterprise'] = 'IoT Enterprise';
    }
}

if(preg_grep('/^ProfessionalN_.*\.esd/i', $filesKeys)) {
    $virtualEditions['ProfessionalWorkstationN'] = 'Pro N for Workstations';
    $virtualEditions['ProfessionalEducationN'] = 'Pro Education N';
    $virtualEditions['EducationN'] = 'Education N';
    $virtualEditions['EnterpriseN'] = 'Enterprise N';
}

styleUpper('downloads', sprintf($s['summaryFor'], "$updateTitle, $selectedLangName, $selectedEditionName"));
?>

<form class="ui normal mini modal virtual-editions form"
action="<?php echo $url; ?>&autodl=3" method="post">
    <div class="header">
        <?php echo $s['selAdditionalEditions']; ?>
    </div>
    <div class="content">
<?php
foreach($virtualEditions as $key => $val) {
    echo <<<EOD
<div class="field">
    <div class="ui checkbox">
        <input type="checkbox" name="virtualEditions[]" value="$key" checked>
        <label>Windows 10 $val</label>
    </div>
</div>

EOD;
}

if(!count($virtualEditions)) echo <<<EOL
<p>{$s['noAdditionalEditions']}</p>

EOL;
?>
    </div>
    <div class="actions">
        <div class="ui ok button">
            <i class="close icon"></i>
            <?php echo $s['cancel']; ?>
        </div>
<?php
if(count($virtualEditions)) echo <<<EOD
<button type="submit" class="ui primary ok button">
    <i class="checkmark icon"></i>
    {$s['ok']}
</button>

EOD;
?>
    </div>
</form>

<div class="ui normal modal virtual-editions-info">
    <div class="header">
        <?php echo $s['learnMore']; ?>
    </div>
    <div class="content">
        <p><?php echo $s['learnMoreAdditionalEditions1']; ?></p>

        <p><b><?php echo $s['learnMoreAdditionalEditions2']; ?></b></p>

        <p><b>Windows 10 Home</b></p>
        <ul>
            <li>Windows 10 Home Single Language</li>
        </ul>
        <p><b>Windows 10 Pro</b></p>
        <ul>
            <li>Windows 10 Pro for Workstations</li>
            <li>Windows 10 Pro Education</li>
            <li>Windows 10 Education</li>
            <li>Windows 10 Enterprise</li>
            <li>Windows 10 Enterprise for Virtual Desktops</li>
            <li>Windows 10 IoT Enterprise</li>
        </ul>
        <p><b>Windows 10 Pro N</b></p>
        <ul>
            <li>Windows 10 Pro for Workstations N</li>
            <li>Windows 10 Pro Education N</li>
            <li>Windows 10 Education N</li>
            <li>Windows 10 Enterprise N</li>
        </ul>
    </div>
    <div class="actions">
        <div class="ui primary ok button">
            <i class="checkmark icon"></i>
            <?php echo $s['ok']; ?>
        </div>
    </div>
</div>

<div class="ui normal tiny modal updates">
    <div class="header">
        <?php echo $s['learnMore']; ?>
    </div>
    <div class="content">
        <p><?php echo $s['learnMoreUpdates1']; ?></p>
        <ul>
            <li>Windows 10</li>
            <li><?php printf($s['systemWithAdk'], 'Windows 8.1'); ?></li>
            <li><?php printf($s['systemWithAdk'], 'Windows 7'); ?></li>
        </ul>
        <p><?php echo $s['learnMoreUpdates2']; ?></p>
    </div>
    <div class="actions">
        <div class="ui primary ok button">
            <i class="checkmark icon"></i>
            <?php echo $s['ok']; ?>
        </div>
    </div>
</div>

<div class="ui horizontal divider">
    <h3><i class="briefcase icon"></i><?php echo $s['summaryOfSelection']; ?></h3>
</div>

<?php
if(!file_exists('packs/'.$updateId.'.json.gz')) {
    styleNoPackWarn();
}

if($updateArch == 'arm64') {
    styleCluelessUserArm64Warn();
}
?>

<div class="ui two columns mobile reversed stackable centered grid">
    <div class="column">
        <a class="ui top attached fluid labeled icon large button"
        href="<?php echo $url; ?>">
            <i class="list icon"></i>
            <?php echo $s['browseList']; ?>
        </a>
        <div class="ui bottom attached segment">
            <?php echo $s['browseListDesc']; ?>
        </div>

        <a class="ui top attached fluid labeled icon large button"
        href="<?php echo $url; ?>&autodl=1">
            <i class="archive icon"></i>
            <?php echo $s['aria2Opt1']; ?>
        </a>
        <div class="ui bottom attached segment">
            <?php echo $s['aria2Opt1Desc']; ?>
        </div>

        <a class="ui top attached fluid labeled icon large blue button"
        href="<?php echo $url; ?>&autodl=2">
            <i class="archive icon"></i>
            <?php echo $s['aria2Opt2']; ?>
        </a>
        <div class="ui bottom attached segment">
            <?php echo $s['aria2Opt2Desc']; ?>
        </div>

        <a class="ui top attached fluid labeled icon large disabled button"
        href="javascript:void(0)" onClick="getVirtualEditions();"
        id="VEConvertBtn">
            <i class="archive icon"></i>
            <?php echo $s['aria2Opt3']; ?>
        </a>
        <div class="ui bottom attached segment">
            <?php echo $s['aria2Opt3Desc']; ?>
            <span id="VEConvertMsgNoJs"><?php echo $s['jsRequiredToConf']; ?></span>
            <span id="VEConvertLearnMoreLink" style="display: none;">
                <a href="javascript:void(0)" onClick="learnMoreVE();">
                    <?php echo $s['learnMore']; ?>
                </a>
            </span>
        </div>
    </div>

    <div class="column">
        <h4><?php echo $s['update']; ?></h4>
        <p><?php echo $updateTitle; ?></p>

        <h4><?php echo $s['lang']; ?></h4>
        <p><?php echo $selectedLangName; ?></p>

        <h4><?php echo $s['edition']; ?></h4>
        <p><?php echo $selectedEditionName; ?></p>

        <h4><?php echo $s['totalDlSize']; ?></h4>
        <p><?php echo $totalSize; ?></p>

<?php
if($hasUpdates) {
    echo <<<INFO
<h4>{$s['additionalUpdates']}</h4>
<p>
    {$s['additionalUpdatesDesc']}

    <a href="javascript:void(0)" onClick="learnMoreUpdates();"
    id="LearnMoreUpdatesLink" style="display: none;">
        {$s['learnMore']}
    </a>
</p>

<a class="ui tiny labeled icon button"
href="./get.php?id=$updateId&pack=0&edition=updateOnly">
    <i class="folder open icon"></i>
    {$s['browseUpdatesList']}
</a>

<script>
document.getElementById('LearnMoreUpdatesLink').style.display = "inline";
</script>

INFO;
}
?>
    </div>
</div>

<div class="ui positive message">
    <div class="header">
        <?php echo $s['aria2NoticeTitle']; ?>
    </div>
    <p><?php echo $s['aria2NoticeText1']; ?></p>

    <p><b><?php echo $s['aria2NoticeText2']; ?></b><br/>
    - Windows: <code>aria2_download_windows.cmd</code><br/>
    - Linux: <code>aria2_download_linux.sh</code><br/>
    </p>

    <p>
<?php
    printf($s['aria2NoticeText3'], '<a href="https://aria2.github.io/">https://aria2.github.io/</a>');
    echo '<br>';
    printf($s['aria2NoticeText4'], '<a href="https://forums.mydigitallife.net/members/abbodi1406.204274/">abbodi1406</a>');
    echo '<br>';
    printf($s['aria2NoticeText5'], '<a href="https://github.com/uup-dump/converter">https://github.com/uup-dump/converter</a>');
?>
    </p>
</div>

<div class="ui fluid tiny three steps">
      <div class="completed step">
            <i class="world icon"></i>
            <div class="content">
                <div class="title"><?php echo $s['chooseLang']; ?></div>
                <div class="description"><?php echo $s['chooseLangDesc']; ?></div>
            </div>
      </div>

      <div class="completed step">
            <i class="archive icon"></i>
            <div class="content">
                <div class="title"><?php echo $s['chooseEdition']; ?></div>
                <div class="description"><?php echo $s['chooseEditionDesc']; ?></div>
            </div>
      </div>

      <div class="active step">
            <i class="briefcase icon"></i>
            <div class="content">
                <div class="title"><?php echo $s['summary']; ?></div>
                <div class="description"><?php echo $s['summaryDesc']; ?></div>
            </div>
      </div>
</div>

<script>
function getVirtualEditions() {
    $('.ui.modal.virtual-editions').modal('show');
}

function learnMoreVE() {
    $('.ui.modal.virtual-editions-info').modal('show');
}

function learnMoreUpdates() {
    $('.ui.modal.updates').modal('show');
}

$('.ui.checkbox').checkbox();

<?php
if(!$disableVE) {
    echo "document.getElementById('VEConvertBtn').classList.remove(\"disabled\");";
}
?>

document.getElementById('VEConvertMsgNoJs').style.display = "none";
document.getElementById('VEConvertLearnMoreLink').style.display = "inline";
</script>

<?php
styleLower();
?>
