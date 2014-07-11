<script type="text/javascript">
    function toggleCheckboxes(source) {
        var checkboxes = document.getElementsByName('del_id[]');
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = source.checked;
        }
    }

    function updateFilters(paramName, paramValue) {
        var newQuery = [];
        var args = {};
        var query = location.search.indexOf('?') > -1 ? location.search.substring(1).split('&') : [];

        for (var pairIndex = 0; pairIndex < query.length; pairIndex++) {
            var param = query[pairIndex].split('=');
            args[param[0]] = param[1];
        }

        args[paramName] = paramValue;
        for (var key in args) {
            if (args.hasOwnProperty(key)) {
                newQuery.push(key + '=' + encodeURIComponent(args[key]));
            }
        }

        self.location.href = '?' + newQuery.join('&');
    }
</script>
<div class="page-header">
    <h1>VAR_tablePhpName Management</h1>
</div>
<div class="pull-right">
    <a class="btn btn-info" href="<?= $this->url(array_merge($VAR_routeParams, array('action' => 'index')), null, true); ?>"><i class="glyphicon glyphicon-refresh"></i> Reset Filters</a>
    <a class="btn btn-success" href="<?= $this->url(array_merge($VAR_routeParams, array('action' => 'create')), null, true); ?>"><i class="glyphicon glyphicon-plus"></i> Add New</a>
</div>
<form class="form-inline" method="get" action="<?= $this->url(array_merge($VAR_routeParams, array('action' => 'index')), null, true); ?>">
    <div>
        <input class="form-control" placeholder="Keywords..." type="text" name="_kw" value="<?php echo htmlspecialchars($this->param_kw); ?>"/>
        <?= $this->formSelect('_sm', $this->param_sm, array('class' => 'form-control'), $VAR_searchableFields); ?>
        <button class="btn" type="submit"><i class="glyphicon glyphicon-search"></i> Search</button>
    </div>
</form>
<form class="form-inline" method="post" action="<?= $this->url(array_merge($VAR_routeParams, array('action' => 'delete')), null, true); ?>" onsubmit="return confirm('Delete selected rows?');">
    <table class="table table-striped table-hover table-condensed">
        <thead>
        VAR_headers
        </thead>
        <tfoot>
        VAR_headers
        </tfoot>
        <tbody>
        <?php foreach ($this->pager as $model) { ?>
            VAR_fields
        <?php } ?>
        </tbody>
    </table>
    <br/>
    <button class="btn btn-danger" type="submit"><i class="glyphicon glyphicon-trash"></i> Delete Selected Rows</button>
</form>
<?= $this->paginate($this->pager); ?>