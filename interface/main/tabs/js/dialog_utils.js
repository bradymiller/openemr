
var opener_list=[];

function set_opener(window,opener)
{
    top.opener_list[window]=opener;
}

function get_opener(window)
{
    return top.opener_list[window];
}
