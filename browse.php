<?php
/*
 * Script: Museum Module Browser
 * Description: Refactored native browse.php with restored Search Engine and robust State Management.
 */
error_reporting(E_ALL & ~E_NOTICE);
session_start();

$central_path = "../../../central/";

include("{$central_path}common/get_post.php");
include("{$central_path}config.php");

if (!isset($_SESSION["permiso"])) {
    header("Location: {$central_path}common/error_page.php");
    die;
}

// Força módulo e blinda o idioma na sessão
$_SESSION["MODULO"] = "museum";
$ABCD_lang = $_SESSION["lang"] ?? 'pt';

include("{$central_path}lang/dbadmin.php");
include("{$central_path}lang/admin.php");
include("{$central_path}lang/prestamo.php");
include("{$central_path}lang/profile.php");

$ABCD_permission = $_SESSION["permiso"];
$ABCD_base = $arrHttp['base'];
$ABCD_cipar = $db_path . $actparfolder . $ABCD_base . ".par";

global $msgstr, $langManager;
if (isset($langManager)) {
    $plugin_lang = $langManager->loadTranslations('museum.tab', $ABCD_lang);
    if (is_array($msgstr) && is_array($plugin_lang)) {
        $msgstr = array_merge($msgstr, $plugin_lang);
    }
}

$table_browser = "tb" . $ABCD_base;
$arrHttp["headings"] = "";
$Formato = "";
$Formato_html = "";

if (isset($arrHttp["pft"]) && trim($arrHttp["pft"]) != "") {
    $Formato = urlencode($arrHttp["pft"]);
} else {
    $pft_name = explode('|', trim($table_browser));
    $table_browser = $pft_name[0] . (strpos($pft_name[0], '.pft') === false ? ".pft" : "");
    $base_path = $db_path . $arrHttp["base"] . "/pfts/" . $ABCD_lang . "/";

    if (file_exists($base_path . $table_browser)) $Formato = "@" . $base_path . $table_browser;
    if (file_exists($base_path . "tb" . $ABCD_base . "_html.pft")) $Formato_html = "@" . $base_path . "tb" . $ABCD_base . "_html.pft";

    $head = $base_path . "tbtit.tab";
    if (file_exists($head)) {
        $arrHttp["headings"] = implode("\r", file($head, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) . "\r";
    }
}

// Configuração de Parâmetros da URL
$page = $_REQUEST['page'] ?? 1;
$show = $_REQUEST['range'] ?? 10;
$ABCD_option = $arrHttp['option'] ?? "sort";
$ABCD_sortkey = $arrHttp['sortkey'] ?? "mfn";
$ABCD_reverse = $_REQUEST['reverse'] ?? 'Off';

$parameters = "&modulo=museum&range={$show}&reverse={$ABCD_reverse}&option={$ABCD_option}&sortkey={$ABCD_sortkey}";

// --- MOTOR DE BUSCA RESTAURADO ---
if (isset($arrHttp["Expresion"]) && trim($arrHttp["Expresion"]) !== "") {
    $arrHttp["Expresion"] = stripslashes($arrHttp["Expresion"]);
    $Expresion = trim($arrHttp["Expresion"]);
    $Expresion = str_replace("  ", " ", $Expresion);
    $Expresion = str_replace('("', "", $Expresion);
    $Expresion = str_replace('")', "", $Expresion);
    $xor = "¬or¬";
    $xand = "¬and¬";
    $Expresion = str_replace(" {", "{", $Expresion);
    $Expresion = str_replace(" or ", $xor, $Expresion);
    $Expresion = str_replace("+", $xor, $Expresion);
    $Expresion = str_replace(" and ", $xand, $Expresion);
    $Expresion = str_replace("*", $xand, $Expresion);

    $nse = -1;
    $subex = [];
    while (is_integer(strpos($Expresion, '"'))) {
        $nse = $nse + 1;
        $pos1 = strpos($Expresion, '"');
        $xpos = $pos1 + 1;
        $pos2 = strpos($Expresion, '"', $xpos);
        $subex[$nse] = trim(substr($Expresion, $xpos, $pos2 - $xpos));
        if ($pos1 == 0) {
            $Expresion = "{" . $nse . "}" . substr($Expresion, $pos2 + 1);
        } else {
            $Expresion = substr($Expresion, 0, $pos1 - 1) . "{" . $nse . "}" . substr($Expresion, $pos2 + 1);
        }
    }
    $Expresion = str_replace(" ", "*", $Expresion);
    while (is_integer(strpos($Expresion, "{"))) {
        $pos1 = strpos($Expresion, "{");
        $pos2 = strpos($Expresion, "}");
        $ix = substr($Expresion, $pos1 + 1, $pos2 - $pos1 - 1);
        if ($pos1 == 0) {
            $Expresion = $subex[$ix] . substr($Expresion, $pos2 + 1);
        } else {
            $Expresion = substr($Expresion, 0, $pos1) . " " . $subex[$ix] . " " . substr($Expresion, $pos2 + 1);
        }
    }
    $Expresion = str_replace("¬", " ", $Expresion);
    $Expresion = urlencode($Expresion);

    // Agora a expressão filtrada é repassada para o WXIS
    $parameters .= "&Expresion=TW_" . $Expresion;
}
// ----------------------------------

if (isset($arrHttp["unlock"]) and $arrHttp["Mfn"] != "New") {
    if (isset($arrHttp["Status"]) and $arrHttp["Status"] != 0) $IsisScript = $xWxis . "eliminarregistro.xis";
    else $IsisScript = $xWxis . "unlock.xis";
    $query = "&base=" . $arrHttp["base"] . "&cipar=$db_path" . $actparfolder . $arrHttp["base"] . ".par&Mfn=" . $arrHttp["Mfn"] . "&login=" . $_SESSION["login"];
    include("{$central_path}common/wxis_llamar.php");
}

function custom_pagination($page, $totalpage, $link, $show)
{
    global $msgstr;
    if ($totalpage == 0) return '<div class="navpage"><span class="current">' . $msgstr['Page'] . ' 0 ' . $msgstr['de'] . ' 0</span></div>';
    $link = str_replace("%2A", "*", $link);
    $link = str_replace("%24", "*", $link);
    $nav_page = '<div class="navpage"><span class="current">' . $msgstr['Page'] . ' ' . $page . ' ' . $msgstr['de'] . ' ' . $totalpage . ': </span>';
    $limit_nav = 3;
    $start = ($page - $limit_nav <= 0) ? 1 : $page - $limit_nav;
    $end = $page + $limit_nav > $totalpage ? $totalpage : $page + $limit_nav;
    if ($page + $limit_nav >= $totalpage && $totalpage > $limit_nav * 2) $start = $totalpage - $limit_nav * 2;
    if ($start != 1) $nav_page .= '<span class="item"><a href="' . sprintf($link, 1) . '"> 1 </a></span>';
    if ($start > 2) $nav_page .= '<span class="current">...</span>';
    if ($page > 5) $nav_page .= '<span class="item"><a href="' . sprintf($link, $page - 5) . '">&laquo;</a></span>';
    for ($i = $start; $i <= $end; $i++) {
        if ($page == $i) $nav_page .= '<span class="current">' . $i . '</span>';
        else $nav_page .= '<span class="item"><a href="' . sprintf($link, $i) . '"> ' . $i . ' </a></span>';
    }
    if ($page + 3 < $totalpage) $nav_page .= '<span class="item"><a href="' . sprintf($link, $page + 4) . '">&raquo;</a></span>';
    if ($end + 1 < $totalpage) $nav_page .= '<span class="current">...</span>';
    if ($end != $totalpage) $nav_page .= '<span class="item"><a href="' . sprintf($link, $totalpage) . '"> ' . $totalpage . '</a></span>';
    $nav_page .= '</div>';
    return $nav_page;
}

// Gatilho WXIS
$query = "&base=" . $ABCD_base . "&cipar=" . $ABCD_cipar . "&Formato=" . $Formato . $parameters;
$IsisScript = $xWxis . "browse.xis";
include("{$central_path}common/wxis_llamar.php");

if (strpos($ABCD_base, '^') !== false) {
    $b = explode('^', $ABCD_base);
    $ABCD_base = substr($b[1], 1);
    $arrHttp["base"] = $ABCD_base;
}

$archivo_tit = $db_path . $ABCD_base . "/pfts/" . $ABCD_lang . "/tbtit.tab";

function read_collumns($archivo_tit)
{
    global $ABCD_reverse, $db_path, $ABCD_base, $ABCD_lang;
    $class_order = ($ABCD_reverse === "On") ? '<i class="fas fa-sort-amount-down"></i>' : '<i class="fas fa-sort-amount-down-alt"></i>';
    echo "<tr><th>" . $class_order . "</th><th>MFN</th>";
    $fallbacks = [$archivo_tit, $db_path . $ABCD_base . "/pfts/" . $ABCD_lang . "/tbtit.tab", $db_path . $ABCD_base . "/pfts/" . $ABCD_lang . "/tb" . $ABCD_base . "_print.txt"];
    $file_found = false;
    foreach ($fallbacks as $file) {
        if (!empty($file) && file_exists($file)) {
            $fp = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($fp !== false) {
                foreach ($fp as $value) {
                    $value = trim($value);
                    if ($value !== "") {
                        $t = explode('|', $value);
                        foreach ($t as $rot) echo "<th>" . $rot . "</th>";
                    }
                }
                $file_found = true;
                break;
            }
        }
    }
    if (!$file_found) echo "<th>Dados do Registro</th>";
    echo "<th class=\"action\"></th></tr>";
}

function display_sort($db_path, $ABCD_base, $ABCD_lang)
{
    global $msgstr, $ABCD_sortkey;
    unset($fp);
    if (file_exists($db_path . $ABCD_base . "/pfts/" . $ABCD_lang . "/sort.tab")) $fp = file($db_path . $ABCD_base . "/pfts/" . $ABCD_lang . "/sort.tab");
    if (isset($fp)) {
        echo '<option value="">' . $msgstr["select"] . '</option>';
        foreach ($fp as $value) {
            if (trim($value) != "") {
                $pp = explode('|', $value);
                $selected = (trim($pp[1]) == $ABCD_sortkey) ? 'selected' : "";
                echo '<option value="' . trim($pp[1]) . '" ' . $selected . '>' . $pp[0] . '</option>';
            }
        }
    }
}

include("{$central_path}common/header.php");
include("{$central_path}common/institutional_info.php");

$posts = array();
foreach ($contenido as $line) {
    $line = trim($line);
    if ($line != "") array_push($posts, explode('|', $line));
}
$total_lines = count($posts);
$totalpage = ceil($total_lines / $show);
$start = ($page * $show) - $show;
$end_l = ($page * $show) - ($total_lines + $show);
$first_post = ($page - 1) * $show;
$last_post = $page + 1 * $show;

function generate_table($contenido, $first_post, $last_post, $show, $total_lines, $end_l)
{
    global $msgstr, $ABCD_reverse, $ABCD_sortkey, $ABCD_base;
    $i = 1;
    $input_array = array_slice($contenido, $first_post, $last_post);
    foreach ($input_array as $line) {
        if ($i == $show + 1) break;
        if ($line != "") {
            $post = explode('|', $line);
            $Isis_Status = $post[0];
            $Isis_Item = $post[1];
            $Isis_Total = $post[2];
            $mfn = $post[3];
            echo "<style>td." . $ABCD_sortkey . " { font-weight: bold; background: var(--cyan); }</style>";
            if ($Isis_Status == 1) echo "<style>.bg_status-" . $Isis_Item . "{ color: #666; }</style>";
            if ($Isis_Status == -2) echo "<style>.bg_status-" . $Isis_Item . "{ color: #d63031; }</style>";

            $n_lines = ($ABCD_reverse === "On") ? substr($end_l++, 1) : $Isis_Item;
            echo "<tr class=\"bg_status-" . $Isis_Item . "\" onmouseover=\"this.className = 'rowOver';\" onmouseout=\"this.className = 'bg_status-" . $Isis_Item . "';\">\n";
            echo "<td><small>" . $n_lines . "/" . $Isis_Total . "</small></td><td>" . $mfn . "</td>";

            for ($ix = 7; $ix < count($post); $ix++) echo '<td class="' . $post[$ix] . '">' . $post[$ix] . '</td>';

            echo '<td class="action" nowrap>';
            if ($Isis_Status == 0) {
                echo '<button class="button_browse show bt-blue" type="button" onclick="Mostrar(' . $mfn . ')"><i class="far fa-eye" title="' . $msgstr["show"] . '"></i> ' . $msgstr["show"] . '</button>';
                echo '<button class="button_browse edit bt-green" type="button" onclick="Editar(' . $mfn . ',' . $Isis_Status . ')"><i class="fas fa-edit" title="' . $msgstr["edit"] . '"></i> ' . $msgstr["edit"] . '</button>';
                echo '<button class="button_browse delete bt-red" type="button" onclick="Eliminar(' . $mfn . ')"><i class="far fa-trash-alt" title="' . $msgstr["eliminar"] . '"></i> ' . $msgstr["eliminar"] . '</button>';
                if ($ABCD_base === 'spec_receipts') echo '<button class="button_browse bt-blue" type="button" onclick="window.open(\'actions/print_receipt.php?mfn=' . $mfn . '\', \'_blank\')"><i class="fas fa-file-pdf"></i> PDF</button>';
            } else {
                if ($Isis_Status == -2) echo '<button class="button_browse edit" type="button" onclick="Editar(' . $mfn . ',' . $Isis_Status . ')"><i class="fas fa-edit"></i> ' . $msgstr["edit"] . " " . $msgstr["recblock"] . '</button>';
                if ($Isis_Status == 1) echo '<button class="button_browse edit" type="button" onclick="Editar(' . $mfn . ',' . $Isis_Status . ')"><i class="fas fa-edit"></i> ' . $msgstr["edit"] . " " . $msgstr["recdel"] . '</button>';
            }
            echo "</td></tr>";
        }
        $i++;
    }
}
?>

<div class="sectionInfo">
    <div class="breadcrumb">
        <?php echo $msgstr["admin"] . " (" . $arrHttp["base"] . ") | " . $total_lines . " " . $msgstr["registros"]; ?>
    </div>
    <div class="toolbar-dataentry">
        <a href="javascript:Crear()" class="bt-tool" title="<?php echo $msgstr["crear"] ?>"><i class="fas fa-plus-circle" style="font-size: 24px;"></i></a>
        <a href="javascript:advancedSearch()" class="bt-tool" title="<?php echo $msgstr["advsearch"]; ?>"><i class="fas fa-search-plus" style="font-size: 24px;"></i></a>

        <form name="print" method="post" action="<?php echo $central_path; ?>dataentry/print.php">
            <input type="hidden" name="base" value="<?php echo $arrHttp["base"] ?>">
            <input type="hidden" name="cipar" value="<?php echo $arrHttp["base"] ?>.par">
            <input type="hidden" name="tipof" value="CT">
            <input type="hidden" name="print_content" value="<?php echo $msgstr["admin"] . " (" . $arrHttp["base"] . ") | " . $total_lines . " " . $msgstr["registros"]; ?>">
            <?php if (isset($arrHttp["Expresion"])) echo '<input type="hidden" name="Expresion" value="' . str_replace("%2A", "*", $Expresion) . '">'; ?>
            <input type="hidden" name="headings" value="<?php echo $arrHttp["headings"]; ?>">
            <input type="hidden" name="pft" value="<?php echo $Formato_html; ?>">
            <input type="hidden" name="vp" value="S">
            <a href="#" class="bt-tool" onclick="EnviarForma('P')" title="<?php echo $msgstr["Print"] ?>"><i class="fas fa-print" style="font-size: 24px;"></i></a>
        </form>

        <form name="spreadsheet" method="post" action="<?php echo $central_path; ?>dataentry/print.php">
            <input type="hidden" name="base" value="<?php echo $arrHttp["base"] ?>">
            <input type="hidden" name="cipar" value="<?php echo $arrHttp["base"] ?>.par">
            <input type="hidden" name="tipof" value="CT">
            <?php if (isset($arrHttp["Expresion"])) echo '<input type="hidden" name="Expresion" value="' . str_replace("%2A", "*", $Expresion) . '">'; ?>
            <input type="hidden" name="headings" value="<?php echo $arrHttp["headings"]; ?>">
            <input type="hidden" name="print_content" value="<?php echo $msgstr["admin"] . " (" . $arrHttp["base"] . ") | " . $total_lines . " " . $msgstr["registros"]; ?>">
            <input type="hidden" name="pft" value="<?php echo $Formato_html; ?>">
            <input type="hidden" name="vp" value="TB">
            <a class="bt-tool" onclick="EnviarForma('TB')" title="<?php echo $msgstr["wsproc"] ?>"><i class="fas fa-file-excel" style="font-size: 24px;"></i></a>
        </form>

        <a href="index.php?lang=<?php echo htmlspecialchars($ABCD_lang); ?>" class="bt-tool" title="<?php echo $msgstr["back"] ?>"><i class="fas fa-home" style="font-size: 24px;"></i></a>
    </div>
</div>


<style>
    .museum-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(3px);
    }

    .museum-modal-content {
        background-color: #f8f9fa;
        margin: 2% auto;
        padding: 0;
        border-radius: 8px;
        width: 95%;
        height: 92%;
        position: relative;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }

    .museum-modal-close {
        color: #fff;
        background-color: #dc3545;
        position: absolute;
        right: -15px;
        top: -15px;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        font-size: 24px;
        font-weight: bold;
        line-height: 33px;
        text-align: center;
        cursor: pointer;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        z-index: 10000;
        transition: background-color 0.2s;
    }

    .museum-modal-close:hover {
        background-color: #c82333;
    }

    #modalIframe {
        width: 100%;
        height: 100%;
        border-radius: 8px;
        border: none;
    }
</style>

<?php if (isset($arrHttp["error"])) echo "<div><font color=red>" . $arrHttp["error"] . "</font></div>"; ?>

<div class="middle list">
    <div class="searchBoxBrowser">
        <div class="f_left">
            <!-- Formulário de Busca Nativo (HTML GET) -->
            <form name="forma1" class="formsearch" action="browse.php" method="GET">
                <label><?php echo $msgstr["buscar"] ?>: </label>
                <input type="hidden" name="base" value="<?php echo htmlspecialchars($ABCD_base); ?>">
                <input type="hidden" name="lang" value="<?php echo htmlspecialchars($ABCD_lang); ?>">
                <input type="hidden" name="reverse" value="<?php echo htmlspecialchars($ABCD_reverse); ?>">
                <input type="hidden" name="sortkey" value="<?php echo htmlspecialchars($ABCD_sortkey ?? ''); ?>">
                <input type="hidden" name="range" value="<?php echo htmlspecialchars($show); ?>">
                <input type="text" name="Expresion" placeholder="<?php echo $msgstr["m_busquedalibre"] ?>..." class="textEntry b_search" value="<?php if (isset($arrHttp["Expresion"])) echo htmlspecialchars(stripslashes($arrHttp["Expresion"])); ?>" />
                <button type="submit" class="bt-blue"><i class="fas fa-search"></i> <?php echo $msgstr["buscar"] ?></button>
            </form>
        </div>

        <div class="f_right">
            <!-- Limpar Filtros -->
            <form action="browse.php" method="GET" style="display:inline;">
                <input type="hidden" name="base" value="<?php echo htmlspecialchars($ABCD_base); ?>">
                <input type="hidden" name="lang" value="<?php echo htmlspecialchars($ABCD_lang); ?>">
                <input type="hidden" name="reverse" value="<?php echo htmlspecialchars($ABCD_reverse); ?>">
                <input type="hidden" name="option" value="sort">
                <button type="submit" class="bt-blue"><i class="fas fa-redo-alt"></i> <?php echo $msgstr["borrar"]; ?></button>
            </form>
            <!-- Mostrar Excluídos -->
            <form action="browse.php" method="GET" style="display:inline;">
                <input type="hidden" name="base" value="<?php echo htmlspecialchars($ABCD_base); ?>">
                <input type="hidden" name="lang" value="<?php echo htmlspecialchars($ABCD_lang); ?>">
                <input type="hidden" name="reverse" value="<?php echo htmlspecialchars($ABCD_reverse); ?>">
                <?php if ($ABCD_option != 'showdeleted' && isset($arrHttp["Expresion"])): ?>
                    <input type="hidden" name="Expresion" value="<?php echo htmlspecialchars(stripslashes($arrHttp["Expresion"])); ?>">
                <?php endif; ?>
                <input type="hidden" name="option" value="<?php echo $ABCD_option == 'showdeleted' ? 'sort' : 'showdeleted'; ?>">
                <button type="submit" class="<?php echo $ABCD_option == 'showdeleted' ? 'bt-green' : 'bt-blue'; ?>">
                    <i class="far <?php echo $ABCD_option == 'showdeleted' ? 'fa-eye-slash' : 'fa-eye'; ?>"></i> <?php echo $msgstr["showdelrec"]; ?>
                </button>
            </form>
        </div>
    </div>

    <div class="SubsearchBoxBrowser">
        <div class="f_left">
            <h2><?php echo $total_lines . " " . $msgstr["registros"]; ?></h2>
            <label><?php echo $msgstr["show"]; ?>: </label>
            <select name="range" id="range" class="textEntry" onchange="setGetParameter('range', this.value)">
                <?php if (!empty($_REQUEST['range'])): ?>
                    <option value="<?php echo $_GET['range']; ?>" selected><?php echo $_GET['range']; ?></option>
                <?php else: ?>
                    <option value="" disabled selected><?php echo $msgstr["select"]; ?></option>
                <?php endif; ?>
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <label><?php echo $msgstr["orderby"]; ?>: </label>
            <select name="sortkey" class="textEntry" onchange="setGetParameter('sortkey', this.value)">
                <?php echo display_sort($db_path, $ABCD_base, $ABCD_lang); ?>
            </select>
            <form style="display:inline;">
                <label>Reverse: </label>
                <select name="reverse" class="textEntry" onchange="setGetParameter('reverse', this.value)">
                    <option value="" disabled <?php if (empty($ABCD_reverse)) echo 'selected'; ?>><?php echo $msgstr["select"]; ?></option>
                    <option value="On" <?php if ($ABCD_reverse == "On") echo 'selected'; ?>>On</option>
                    <option value="Off" <?php if ($ABCD_reverse == "Off") echo 'selected'; ?>>Off</option>
                </select>
            </form>
        </div>
    </div>

    <div class="formContent">
        <table class="listTable browse">
            <?php echo read_collumns($archivo_tit); ?>
            <?php echo generate_table($contenido, $first_post, $last_post, $show, $total_lines, $end_l); ?>
            <?php echo read_collumns($archivo_tit); ?>
        </table>
        <div class="tMacroActions Browser">
            <div class="spacer">&#160;</div>
            <?php echo custom_pagination($page, $totalpage, 'browse.php?action=detail&page=%s&base=' . $arrHttp['base'] . '&range=' . $show . '&lang=' . $ABCD_lang . $parameters, $show); ?>
            <div class="spacer">&#160;</div>
        </div>
    </div>
</div>

<div id="museumModal" class="museum-modal">
    <div class="museum-modal-content">
        <span class="museum-modal-close" onclick="fecharModal()" title="Fechar e Atualizar">&times;</span>
        <iframe name="modalIframe" id="modalIframe"></iframe>
    </div>
</div>


<script type="text/javascript">
    function setGetParameter(paramName, paramValue) {
        var url = window.location.href;
        var queryString = location.search.substring(1);
        var newQueryString = "";
        if (url.indexOf(paramName + "=") >= 0) {
            var decode = function(s) {
                return decodeURIComponent(s.replace(/\+/g, " "));
            };
            var keyValues = queryString.split('&');
            for (var i in keyValues) {
                var key = keyValues[i].split('=');
                if (key.length > 1) {
                    if (newQueryString.length > 0) newQueryString += "&";
                    newQueryString += (decode(key[0]) == paramName) ? key[0] + "=" + encodeURIComponent(paramValue) : key[0] + "=" + key[1];
                }
            }
        } else {
            newQueryString = (url.indexOf("?") < 0) ? "?" + paramName + "=" + paramValue : queryString + "&" + paramName + "=" + paramValue;
        }
        window.location.href = window.location.href.split('?')[0] + "?" + newQueryString;
    }

    function advancedSearch() {
        base = '<?php echo $arrHttp["base"] ?>';
        cipar = base + ".par";
        Url = "<?php echo $central_path; ?>dataentry/buscar.php?Opcion=formab&prologo=prologoact&Target=s&Tabla=cGlobal&base=" + base + "&cipar=" + cipar;
        msgwin = window.open(Url, "Buscar", "menu=no, resizable,scrollbars,width=750,height=400");
        msgwin.focus();
    }

    function EnviarForma(vp) {
        if (vp == "P") {
            document.print.vp.value = "S";
            document.print.target = "VistaPrevia";
            msgwin = window.open("", "VistaPrevia", "width=400,top=0,left=0,resizable, status, scrollbars");
        } else {
            document.print.vp.value = vp;
            document.print.target = "";
        }
        document.print.submit();
        msgwin.focus();
    }

    function Editar(Mfn, Status) {
        document.editar.Mfn.value = Mfn;
        document.editar.Status.value = Status;
        document.editar.Opcion.value = "editar";
        document.getElementById('museumModal').style.display = "block";
        document.editar.submit();
        iniciarOcultacaoIframe(); // Aciona o vigia
    }

    function Crear() {
        document.editar.Mfn.value = "New";
        document.editar.Opcion.value = "nuevo";
        document.getElementById('museumModal').style.display = "block";
        document.editar.submit();
        iniciarOcultacaoIframe(); // Aciona o vigia
    }

    function fecharModal() {
        // Desliga o vigia para economizar memória
        if (window.iframeVigia) clearInterval(window.iframeVigia);
        document.getElementById('museumModal').style.display = "none";
        document.getElementById('modalIframe').src = "about:blank"; // Limpa a memória do iframe
        window.location.reload(); // Recarrega o browse.php atualizando a tabela com os novos dados
    }

    // Função Vigia: Procura o botão de cabeçalho do ABCD a cada 100ms e força o sumiço dele
    function iniciarOcultacaoIframe() {
        if (window.iframeVigia) clearInterval(window.iframeVigia);

        window.iframeVigia = setInterval(function() {
            try {
                var iframeDoc = document.getElementById('modalIframe').contentWindow.document;
                if (iframeDoc && iframeDoc.body) {
                    var botoes = iframeDoc.querySelectorAll('.button_browse.show');
                    for (var i = 0; i < botoes.length; i++) {
                        botoes[i].style.display = 'none';
                        botoes[i].style.visibility = 'hidden';
                    }
                }
            } catch (e) {
                // Ignora erros de bloqueio de origem enquanto o iframe carrega
            }
        }, 100);
    }

    function Mostrar(Mfn) {
        msgwin = window.open("<?php echo $central_path; ?>dataentry/show.php?base=<?php echo $arrHttp["base"] ?>&cipar=<?php echo $arrHttp["base"] ?>.par&Mfn=" + Mfn + "&encabezado=s&Opcion=editar", "show", "width=600,height=400,scrollbars, resizable");
        msgwin.focus();
    }

    function Eliminar(Mfn) {
        if (confirm('<?php echo $msgstr["areysure"] ?>')) {
            document.eliminar.Mfn.value = Mfn;
            document.eliminar.submit();
        }
    }

    // Função "fantasma" para evitar erros do fmt.php nativo do ABCD.
    function PrenderEdicion() {
        console.log("Modo de edição iniciado no modal.");
    }
</script>

<form name="eliminar" method="post" action="<?php echo $central_path; ?>dataentry/eliminar_registro.php">
    <input type="hidden" name="base" value="<?php echo $arrHttp["base"]; ?>">
    <input type="hidden" name="from" value="<?php echo $first_post + 1; ?>">
    <input type="hidden" name="modulo" value="museum">
    <?php if (isset($arrHttp["Expresion"])): ?>
        <input type="hidden" name="Expresion" value="<?php echo urlencode($arrHttp["Expresion"]); ?>">
    <?php endif; ?>
    <input type="hidden" name="Mfn">
    <?php if (isset($arrHttp["encabezado"])) echo "<input type='hidden' name='encabezado' value=''>\n"; ?> <?php if (isset($arrHttp["return"])): ?>
        <input type="hidden" name="showdeleted" value="yes">
        <input type="hidden" name="return" value="<?php echo $arrHttp["return"]; ?>">
    <?php endif; ?>
</form>

<form name="editar" method="post" target="modalIframe" action="<?php echo $central_path; ?>dataentry/fmt.php">
    <input type="hidden" name="from" value="<?php echo $first_post + 1; ?>">
    <input type="hidden" name="base" value="<?php echo $arrHttp["base"]; ?>">
    <input type="hidden" name="cipar" value="<?php echo $arrHttp["base"]; ?>.par">
    <input type="hidden" name="modulo" value="museum">
    <input type="hidden" name="Mfn">
    <input type="hidden" name="lang" value="<?php echo $ABCD_lang; ?>">
    <input type="hidden" name="Status">
    <input type="hidden" name="showdeleted" value="yes">
    <input type="hidden" name="retorno" value="../../content/plugins/museum/browse.php?base=<?php echo htmlspecialchars($ABCD_base); ?>&lang=<?php echo htmlspecialchars($ABCD_lang); ?>">
    <input type="hidden" name="Opcion" value="editar">
    <input type="hidden" name="encabezado" value="">
    <?php if (isset($arrHttp["return"])) echo "<input type='hidden' name='return' value='" . $arrHttp["return"] . "'>\n"; ?>
    <?php if (isset($arrHttp["Expresion"])) echo "<input type='hidden' name='Expresion' value='" . urlencode($arrHttp["Expresion"]) . "'>\n"; ?>
</form>







<?php include("{$central_path}common/footer.php"); ?>