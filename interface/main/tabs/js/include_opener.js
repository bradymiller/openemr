
if(top.tab_mode===true)
{
    if(!opener)
    {
        opener=top.get_opener(window.name);
    }

    window.close=
            function()
            {
                var dialogDiv=top.$("#dialogDiv");
                var frameName=window.name
                var body=top.$("body");
                    var removeFrame=body.find("iframe[name='"+frameName+"']");
                    removeFrame.remove();
                    var removeDiv=body.find("div.dialogIframe[name='"+frameName+"']");
                    removeDiv.remove();
                    if(body.children("div.dialogIframe").length===0)
                    {   
                        dialogDiv.hide();
                    };
                };    
}
