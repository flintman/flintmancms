var user_die = 0;
var email_die = 0;
var password_die = 0;

function validate_reg() {
    if (document . register . username . value == '') {
        alert('Please fill in a username!');
        return false;
    }
    if (document . register . password . value == '') {
        alert('Please fill in a password!');
        return false;
    }
    if (document . register . password2 . value == '') {
        alert('Please fill in the second password!');
        return false;
    }
    if (document . register . email . value == '') {
        alert('Please fill in an email address!');
        return false;
    }

    if (document . register . password2 . value != document . register . password . value) {
        alert('Your Password don\'t match');
        return false;
    }
    email = document.register.email.value;
    AtPos = email.indexOf("@");
    StopPos = email.lastIndexOf(".");

    if (AtPos == -1 || StopPos == -1) {
        alert('Please enter in a vaild Email');
        return false;
    }
    return true;
}

function check_username(name){
    if (window.XMLHttpRequest)
    {// code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp=new XMLHttpRequest();
    }
    else
    {// code for IE6, IE5
        xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange=function()
    {
        if (xmlhttp.readyState==4 && xmlhttp.status==200)
        {
            if(xmlhttp.responseText == "Username Already used"){
                user_die = 1;
            }else{
                user_die = 0;
            }
            document.getElementById("user").innerHTML=xmlhttp.responseText;
        }
    }
    xmlhttp.open("GET","includes/ajax/ajax.php?u="+name,true);
    xmlhttp.send();
    disable_button();
}

function check_email(email){
    if (window.XMLHttpRequest)
    {// code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp=new XMLHttpRequest();
    }
    else
    {// code for IE6, IE5
        xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange=function()
    {
        if (xmlhttp.readyState==4 && xmlhttp.status==200)
        {
            if(xmlhttp.responseText == "Email Already used"){
                email_die = 1;
            }else{
                AtPos = email.indexOf("@");
                StopPos = email.lastIndexOf(".");
                email_die = 0;
            }

            document.getElementById("email").innerHTML=xmlhttp.responseText;
            if (AtPos == -1 || StopPos == -1) {
                email_die = 1;
                document.getElementById("email").innerHTML ="Not a Valid Email address"
            }
                
        }
    }
    xmlhttp.open("GET","includes/ajax/ajax.php?e="+email,true);
    xmlhttp.send();
    disable_button();
}

function check_password(password2){
    password = document . register . password . value;

    if (password == password2){
        document.getElementById("pass").innerHTML="Passwords Match";
        password_die = 0;
    }else{
        password_die = 1;
        document.getElementById("pass").innerHTML="Passwords Don't Match";
    }
    disable_button();
}

function disable_button(){
    if (user_die || email_die || password_die){

        document.getElementById("button").disabled=true;
    }
    else
        document.getElementById("button").disabled=false;
}

