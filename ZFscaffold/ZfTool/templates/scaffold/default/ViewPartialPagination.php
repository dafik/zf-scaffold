<? if ($this->pageCount) { ?>
    <div class="paginationControl">
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