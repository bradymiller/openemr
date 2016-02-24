
<script type="text/html" id="user-data-template">
    <!-- ko with: user -->
        <div id="username">
            <span data-bind="text:fname"></span>
            <span data-bind="text:lname"></span>
            <div class="userfunctions">
                <div data-bind="click: editSettings"><?php echo xlt("Settings");?></div>
                <div data-bind="click: changePassword"><?php echo xlt("Change Password");?></div>
                <div data-bind="click: logout"><?php echo xlt("Logout");?></div>                
            </div>
        </div>
    <!-- /ko -->
</script>
