
function user_data_view_model(username,fname,lname,authGrp)
{
    var self=this;
    self.username=ko.observable(username);
    self.fname=ko.observable(fname);
    self.lname=ko.observable(lname);
    self.authorization_group=ko.observable(authGrp);
    return this;
    
}

function logout()
{
    top.window.location=webroot_url+"/interface/logout.php"
}

function aboutPage()
{
    navigateTab(webroot_url+"/interface/main/about_page.php","msc");
    activateTabByName("msc",true);
}
