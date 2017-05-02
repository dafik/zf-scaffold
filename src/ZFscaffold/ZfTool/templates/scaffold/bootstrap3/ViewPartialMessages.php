<? if (count($this->messages['error']) > 0) { ?>
    <? foreach ($this->messages['error'] as $error) { ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <? } ?>
<? } ?>
<? if (count($this->messages['notice']) > 0) { ?>
    <? foreach ($this->messages['notice'] as $notice) { ?>
        <div class="alert alert-warning"><?= $notice ?></div>
    <? } ?>
<? } ?>
<? if (count($this->messages['confirmation']) > 0) { ?>
    <? foreach ($this->messages['confirmation'] as $confirmation) { ?>
        <div class="alert alert-success"><?= $confirmation ?></div>
    <? } ?>
<? } ?>
<? if (count($this->messages[Dfi\Controller\Action\Helper\Messages::TYPE_DEBUG]) > 0) { ?>
    <? foreach ($this->messages[Dfi\Controller\Action\Helper\Messages::TYPE_DEBUG] as $debug) { ?>
        <div class="alert alert-info"><?= $debug ?></div>
    <? } ?>
<? } ?>

