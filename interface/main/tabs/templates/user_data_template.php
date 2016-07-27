
<script type="text/html" id="user-data-template">
    <!-- ko with: user -->
        <div id="username" title="<?php echo xla('Authorization group') ?>">
            <span data-bind="text:fname"></span>
            <span data-bind="text:lname"></span>
            <div class="userfunctions">
                <div data-bind="click: aboutPage"><?php echo xlt("About");?></div>
                <div data-bind="click: logout"><?php echo xlt("Logout");?></div>
                
            </div>
        </div>
    <!-- /ko -->
</script>
