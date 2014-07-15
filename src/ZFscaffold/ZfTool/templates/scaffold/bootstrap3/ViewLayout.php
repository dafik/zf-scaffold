<?=$this->doctype() ?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl">
<?= $this->partial('header.phtml') ?>
<body role="document">
<?= $this->layout()->navbar ?>
<div class="container" role="main">
    <?= $this->layout()->messages ?>
    <?= $this->layout()->content ?>
</div>
</body>
