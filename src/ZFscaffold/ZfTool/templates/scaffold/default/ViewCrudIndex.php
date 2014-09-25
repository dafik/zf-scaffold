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
<div style="text-align:right">
    <a href="<?= $this->url(array('action' => 'index'), null, true); ?>">Reset Filters</a>
    - <a href="<?= $this->url(array('action' => 'create'), null, true); ?>">Add New</a>
</div>
<br/>
<form method="get" action="<?= $this->url(array('action' => 'index'), null, true); ?>">
    <div>
        <label for="_kw">Search for:</label><input id="_kw" type="text" name="_kw" value="<?= htmlspecialchars($this->param_kw); ?>"/> in
        <?= $this->formSelect('_sm', $this->param_sm, array(), $VAR_searchableFields); ?>
        <input type="submit" value="Go"/>
    </div>
</form>
<form method="post" action="<?= $this->url(array('action' => 'delete'), null, true); ?>" onsubmit="return confirm('Delete selected rows?');">
    <table width="100%" border="1" style="border-collapse:collapse" cellspacing="0" cellpadding="3">
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
    <input type="submit" value="Delete Selected Rows"/>
</form>
<?= $this->paginationControl($this->paginator, 'Sliding', 'pagination_control.phtml'); ?>