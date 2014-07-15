<nav class="navbar navbar-default navbar-inverse" role="navigation">
    <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="/<?= $this->module ?>">Mylime Admin</a>
        </div>
        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Tables <span class="caret"></span></a>
                    <?= $this->navigation($this->menu)->menu()->setUlClass('dropdown-menu')->render() ?>
                </li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li><a href="/<?= $this->module ?>/logout">Logout</a></li>
            </ul>
        </div>
        <!-- /.navbar-collapse -->
    </div>
    <!-- /.container-fluid -->
</nav>
<!--<div class="navbar navbar-default" role="navigation">-->
<? //= $this->navigation($this->layout()->menu)->menu()->render() ?>
<!--</div>-->
