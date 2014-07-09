<? /*
<? if ($this->pageCount) { ?>
    <div class="pagination">
        <!-- Previous page link -->
        <? if (isset($this->previous)) { ?>
            <a href="<?= $this->url($_GET + array('page' => $this->previous)); ?>">
                &lt; Previous
            </a> |
        <? } else { ?>
            <span class="disabled">&lt; Previous</span> |
        <? } ?>

        <!-- Numbered page links -->
        <? foreach ($this->pagesInRange as $page) { ?>
            <? if ($page != $this->current) { ?>
                <a href="<?= $this->url($_GET + array('page' => $page)); ?>">
                    <?= $page; ?>
                </a> |
            <? } else { ?>
                <?= $page; ?> |
            <? } ?>
        <? } ?>

        <!-- Next page link -->
        <? if (isset($this->next)) { ?>
            <a href="<?= $this->url($_GET + array('page' => $this->next)); ?>">
                Next &gt;
            </a>
        <? } else { ?>
            <span class="disabled">Next &gt;</span>
        <? } ?>
    </div>
<? } ?>
 */
?>

<? $pager = $this->pager; /* @var $pager PropelModelPager */ ?>
<? if ($pager->haveToPaginate()) { ?>
    <ul class="pagination">
        <li><a href="<?= $this->url($_GET + array('page' => $pager->getFirstPage())) ?>">&lt;&lt;</a></li>
        <li<?= ($pager->getPage() == 1 ? ' class="disabled"' : '') ?>>
            <a href="<?= $this->url($_GET + array('page' => $pager->getPreviousPage())) ?>">&lt;</a>
        </li>
        <? foreach ($pager->getLinks() as $page) { ?>
            <li class="<?= ($pager->getPage() == $page ? 'active' : '') ?>">
                <a href="<?= $this->url($_GET + array('page' => $page)) ?>"><?= $page ?> </a>
            </li>
        <? } ?>
        <li<?= ($pager->getPage() == $pager->getLastPage() ? ' class="disabled"' : '') ?>>
            <a href="<?= $this->url($_GET + array('page' => $pager->getNextPage())) ?>">&gt;</a>
        </li>
        <li><a href="<?= $this->url($_GET + array('page' => $pager->getLastPage())) ?>">&gt;&gt;</a></li>
    </ul>
<? } ?>