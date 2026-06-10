<?
include_once $_SERVER['DOCUMENT_ROOT'] . "/wa-system/request/waRequest.class.php";

$bookmark_p = waRequest::cookie('shop_bookmark', array(), waRequest::TYPE_ARRAY_INT);
$compare_p = waRequest::cookie('shop_compare', array(), waRequest::TYPE_ARRAY_INT);
$viewed_p = waRequest::cookie('shop_viewed', array(), waRequest::TYPE_ARRAY_INT);
?>
<aside id="footer-pane" class="hide-on-med-and-down">
    <div class="container">
        <div class="row-grid"><div class="table-grid">
                <div class="col-grid">
                    <a id="bookmark-link" href="/search/?addition=bookmark" rel="nofollow" class="addition-link gray" data-hint="Товары в закладках">
                        <i class="material-icons mi-2x">&#xE838;</i>
                        <span class="text">Товары в закладках</span> (<span class="count"><?=count($bookmark_p)?></span>)</a>
                </div>
                <div class="col-grid">
                    <a id="compare-link" data-href="/compare/" href="/compare/" rel="nofollow" class="addition-link gray" data-hint="Товары для сравнения">
                        <i class="material-icons mi-2x">&#xE01D;</i>
                        <span class="text">Товары для сравнения</span> (<span class="count"><?=count($compare_p)?></span>)</a>
                </div>
                <div class="col-grid">
                    <a id="viewed-link" href="/search/?addition=viewed" rel="nofollow" class="addition-link gray">
                        <i class="material-icons mi-2x">&#xE8F4;</i>
                        <span class="text">Просмотренные товары</span> (<span class="count"><?=count($viewed_p)?></span>)</a>
                </div>
                <div class="col-grid stay-app">
                    <a href="/ostavit-zayavku/?page-send=<?=$_SERVER['HTTP_HOST']?>" class="addition-link">
                        <i class="material-icons mi-2x">&#xe85d;</i>&nbsp;
                        <span>Оставить заявку</span>
                    </a>
                </div>
                <div class="col-grid min-width stay-chat">
                    <button type="button" class="addition-link almamed-chat-open" id="almamed-chat-open">
                        <i class="material-icons mi-2x">&#xe0b7;</i>&nbsp;
                        <span>Напишите нам</span>
                    </button>
                </div>
            </div></div>
    </div>
</aside>
