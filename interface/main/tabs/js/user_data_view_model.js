
function user_data_view_model(username,fname,lname,authGrp)
{
    var self=this;
    self.username=ko.observable(username);
    self.fname=ko.observable(fname);
    self.lname=ko.observable(lname);
    self.authorization_group=ko.observable(authGrp);
    return this;
    
}

function editSettings()
{
    navigateTab(webroot_url+"/interface/super/edit_globals.php?mode=user","prf0");
    activateTabByName("prf0",true);
}

function changePassword()
{
    navigateTab(webroot_url+"/interface/usergroup/user_info.php","msc");
    activateTabByName("msc",true);
}

function logout()
{
    top.window.location=webroot_url+"/interface/logout.php"
}
