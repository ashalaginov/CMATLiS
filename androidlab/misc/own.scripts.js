/**
 * \file own.scripts.js
 * \brief     JavaScript functionality for Layout generation
 * \author Andrey Shalaginov <andrii.shalaginov@hig.no>
 * \date September-December 2012
 * \version   1.0
 */

/**
 * Show or Hide content in a table as a <tr> area
 * @param divId ID of div element, which will be hidden/shown
 */
function toggleDiv(divId) {
    $("#"+divId).toggle();
}

/**
 * Check or Uncheck all checkboxes in the defined form
 * @param formname ID of form element, in which checkboxes are located
 */
function checkAll(formname)
{
    var checkboxes = new Array(); 
    checkboxes = document[formname].getElementsByTagName("input");
    var flag=0;
    for (var i=0; i<checkboxes.length; i++)  {
        //Check checkboxes only after Check/UnCheck ALL button
        if(checkboxes[i].name=="checker")
            flag=1;
        if (checkboxes[i].type == "checkbox"&&flag==1)   {
            checkboxes[i].checked = (checkboxes[i].checked==true)?false:true;
        }
    }
}

/**
 * Beautiful appearing and disapearing of performed tests on the page in AJAX area
 */
function checkEmu(){
    $('#tests_state').load('checkTests.php',  function() {
        $('#tests_state').fadeOut("slow").hide("slow")
        $('#tests_state').fadeIn("slow").show("slow");
    });
}

/**
 * Beautiful appearing and disapearing of emulator state on the page in AJAX area
 */
function checkTests(){
    $('#emulator_state').load('checkEmu.php',  function() {
        $('#emulator_state').fadeOut("slow").hide("slow")
        $('#emulator_state').fadeIn("slow").show("slow");
    });

}

/**
 * Launch update functions simultaneously
 */
function periodicalFunctions() {	
    checkEmu();
    checkTests();
}

///Initial Execution of page area update functions
setTimeout("periodicalFunctions()", 0);
///Postponed periodical execution of update functions in 10 sec
setInterval("periodicalFunctions()", 10000);

/**
 * Draw resources usage plots by means of JQplot Library
 * @param chartDiv ID of page element, where chart should be put
 * @param data Numerical data series to be displayed
 * @param texter  Title to be displayed above graphic
 * @param colorer Color of plot on the graphic
 */
function drawChart(chartDiv,data,texter,colorer) {
    $.jqplot (chartDiv, data, {
        title: texter,
        axesDefaults: {
            labelRenderer: $.jqplot.CanvasAxisLabelRenderer
        },
        axes: {
            yaxis: {
                renderer: $.jqplot.LogAxisRenderer, 
                tickDistribution:'power',
                min:0
            },
            xaxis: {
                label:'time of running(s)',
                min:0 
            }
        },  
        series:[{
            color:colorer,
            lineWidth:4, 
            showMarker:false, 
            shadowAngle:45, 
            shadowOffset:1.5, 
            shadowAlpha:.18, 
            shadowDepth:4
        }],
        cursor: {  
            showVerticalLine:true,
            showHorizontalLine:true,
            showTooltip: false,
            zoom:true
        }, 

        highlighter: {
            sizeAdjust: 7.5
        }			
    });
}
