    var vTopBot='TOP'
    function fClick(vX, vY) {
      if(vTopBot != 'TOP') {
        vTopBot='TOP'
        document.myform.bot_x.value=vX
        document.myform.bot_y.value=vY
      } else {
        vTopBot='BOTTON'
        document.myform.top_x.value=vX
        document.myform.top_y.value=vY
      }
    }
    function fHidesShowOnly(vTableID) {
      vTable=document.getElementById(vTableID);
      vFieldValue=vTable.style.display;
      if(vFieldValue=='none') {
        vTable.style.display = 'block';
      } else {
        vTable.style.display = 'none';
      }
    }
    function fCheckAll(vValue) {
      for (var x = 0; x < document.forms['myform'].elements.length; x++) {
        var y = document.forms['myform'].elements[x];
        if(y.name.substr(0,4)==vValue) {
          y.checked=true;
        }
      }
    }
    function fCheckNone(vValue) {
      for (var x = 0; x < document.forms['myform'].elements.length; x++) {
        var y = document.forms['myform'].elements[x];
        if(y.name.substr(0,4)==vValue) {
          y.checked=false;
        }
      }
    }
    function fShowTab(vName) {
      document.getElementById('tab_seed').style.display='none';
      document.getElementById('tab_anim').style.display='none';
      document.getElementById('tab_tree').style.display='none';
      document.getElementById('tab_deco').style.display='none';
      document.getElementById('tab_building').style.display='none';
      document.getElementById('tab_buyanim').style.display='none';
      document.getElementById('tab_buytree').style.display='none';
      document.getElementById('tab_buydeco').style.display='none';

      document.getElementById(vName).style.display='block';
    }
    function fShowHide(vTableID) {
      vTable=document.getElementById(vTableID);
      vFieldValue=vTable.style.display;
      if(vFieldValue=="none") {
        vTable.style.display = "block";
      } else {
        vTable.style.display = "none";
      }
    }