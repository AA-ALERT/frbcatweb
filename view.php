<?php

include ("frb_functions.inc.php");
include ("../../admin/form_functions.inc.php"); // where is this php script?

function renderField($title, $value, $units="")
{
  echo "<tr>\n";
  echo "  <td width='50%' align=left><b>".$title."</b></td>\n";
  if ($units == "")
  {
    echo "  <td colspan=2>".$value."</td>\n";
  }
  else
  {
    echo "  <td width='30%'>".$value."</td>\n";
    echo "  <td width='20%'>".$units."</td>\n";
  }
  echo "</tr>\n";
}

function renderFieldID($title, $value, $id, $units="")
{
  echo "<tr>\n";
  echo "  <td width='50%' align=left><b>".$title."</b></td>\n";
  if ($units == "")
  {
    echo "  <td colspan=2 id='".$id."'>".$value."</td>\n";
  }
  else
  {
    echo "  <td width='30%' id='".$id."'>".$value."</td>\n";
    echo "  <td width='20%'>".$units."</td>\n";
  }
  echo "</tr>\n";
}

#
# view.php
#
?>
<!--#set var="title" value="FRBCAT - View" -->
<!--#set var="siteid" value="883" -->
<!--#set var="menuid" value="16578" --->
<!--#set var="maintainer_name" value="Andrew Jameson" -->
<!--#set var="maintainer_uname" value="ajameson" -->
<!--#set var="additional_head" value="/pulsar/frbcat/additional_head.html" -->
<!--#include virtual="/siteheaders/header.html" -->
<!-- ########################################## -->
<!-- ############  START CONTENT  ############# -->

<?php

$errors = array();

$referer = $_SERVER["HTTP_REFERER"];
$back_to = $referer;

$frb_id = -1;
if (isset($_GET["id"]))
{
  $frb_id = $_GET["id"];
}
else
{
  $errors["id"] = "FRB ID not provided";
}

$link = db_connect();

if ($frb_id > 0)
{
  $observations = getObservations ($link, $frb_id);

  echo "<table width='100%'>\n";
  echo "<tr><td width='50%'>\n";

  echo "<table class='standard' cellpadding=5px width='300px'>\n";
  echo "<tr><th colspan=3>FRB Parameters</th></tr>\n";
  renderField("Name", $observations[0]["name"]);
  renderField("UTC",  $observations[0]["utc"]);
  echo "</table>\n";

  $frb_notes = getFRBNotes($link, $frb_id);
  # get any notes associated with the FRB
  if (count($frb_notes) > 0)
  {
    echo "<p>Notes:</p>\n";
    echo "<ul>\n";
    foreach ($frb_notes as $note)
    {
      list ($day, $time) = explode(" ",$note["last_modified"]);
      $authorstamp = $note["author"]." ".$day;
      echo "<li>".$note["note"].". [".$authorstamp."]</li>\n";
    }
    echo "</ul>\n";
  }

  echo "</td>\n";
  echo "<td width='50%'>\n";

  echo "<form name='getval'>\n";
  echo "<table class='standard'>\n";
  echo "<tr><th colspan=2>Cosmological Parameters</th></tr>\n";

  echo "<tr><td>Omega<SUB>M</SUB></td><td><INPUT TYPE='TEXT' name='tWM' id='tWM' VALUE='0.286' SIZE=4></td></tr>\n";
  echo "<tr><td>H<SUB>o</SUB></td><td><INPUT TYPE='TEXT' name='tH0' id='tH0' VALUE='69.6' SIZE=4></td></tr>\n";
  echo "<tr><td>Omega<SUB>vac</SUB></td><td><INPUT TYPE='TEXT' name='tWV' id='tWV' VALUE='0.714' SIZE=4></td></tr>\n";
  echo "</table>\n";
  echo "<input type='button' onClick='updateCosmologicalParams()' value='Update Derived Params'/>\n";
  echo "</form>\n";
  echo "<font size='-2'>Calculation Method: <a href='http://adsabs.harvard.edu/abs/2006PASP..118.1711W'>Wright (2006, PASP, 118, 1711)</a></font>\n";

  echo "</td>\n";
  echo "</tr>\n";
  echo "</table>\n";

  $js = "";

  foreach ($observations as $obs_idx => $o)
  {
    if ($obs_idx > 0)
      echo "<hr\n>";
    echo "<h2>".ucfirst($o["type"])." Observation at ".ucfirst($o["telescope"])."</h2>\n";

    # get the list of observation notes
    $obs_notes = getObsNotes($link, $o["id"]);

    $radio_obs_params = getRadioObsParams($link, $o["id"]);
    if (count($radio_obs_params) > 1)
      echo "<p>Number of Observations: ".count($radio_obs_params)."</p>\n";


    foreach ($radio_obs_params as $rop_idx => $rop)
    {
      if (count($radio_obs_params) > 1)
      {
        echo "<h3>".ucfirst($o["type."])." Observation #".($rop_idx+1)."</h3>\n";
        echo "<div class='obs_param'>\n";
      }

      echo "<table width='100%'>\n";
      echo "<tr><td width='50%'>\n";

      $pointing_error_arcsecs = $rop["pointing_error"];
      $pointing_error_secs = round($pointing_error_arcsecs / 15);
      $pointing_error_degrees = $pointing_error_arcsecs / 3600;
?>
<table class='standard' cellpadding=5px width='100%'>
  <tr><th colspan=3>Observation Parameters</th></tr>
<?php
      renderField("Telescope", ucfirst($o["telescope"]));
      renderField("Receiver", $rop["receiver"]);
      renderField("Backend", $rop["backend"]);
      renderField("Beam", $rop["beam"]);
      //renderField("Positional Uncertainty", $rop["positional_uncertainty"]);
      renderField("Sampling Time", $rop["sampling_time"], "[ms]");
      renderField("Bandwidth", $rop["bandwidth"], "[MHz]");
      renderField("Centre Frequency", $rop["centre_frequency"], "[MHz]");
      renderField("Number of Polarisations", $rop["npol"]);
      renderField("Channel Bandwidth", $rop["channel_bandwidth"], "[MHz]");
      renderField("Bits per sample", $rop["bits_per_sample"]);
      renderField("Gain", $rop["gain"], "[K/Jy]");
      renderField("System Temperature", $rop["tsys"]);

      $ref = "";
      if ($rop["reference"] != "")
      {
        if ($rop["link"] != "")
        {
          $ref = "<a href='".$rop["link"]."'>";
        }
        list ($first, $rest) = split(" ", $rop["reference"]);
        $ref .= $first." et al";
                if ($rop["link"] != "")
        {
          $ref .= "</a>";
        }
      }
      renderField("Reference", $ref);

      $data_link = "None available";
      if ($o["data_link"] != "")
      {
        $data_link = "<a href='".$o["data_link"]."'>Link to Data Portal</a>";
      }
      renderField("Raw Data", $data_link);
      echo "</table>\n";

      $rop_notes = getRadioObsParamsNotes($link, $rop["id"]);

      # observation notes or ROP notes
      if ((count($obs_notes) + count($rop_notes)) > 0)
      {
        echo "<p>Notes:</p>\n";
        echo "<ul>\n";
        foreach ($obs_notes as $note)
        {
          list ($day, $time) = explode(" ",$note["last_modified"]);
          $authorstamp = $note["author"]." ".$day;
          echo "<li>".$note["note"]." [".$authorstamp."]</li>\n";
        }

        foreach ($rop_notes as $note)
        {
          list ($day, $time) = explode(" ",$note["last_modified"]);
          $authorstamp = $note["author"]." ".$day;
          echo "<li>".$note["note"]." [".$authorstamp."]</li>\n";
        }
        echo "</ul>\n";
      }

      $rmps = getRadioMeasuredParams ($link, $rop["id"]);
      $rmp_notes = getRadioMeasuredParamsNotes ($link, $rop["id"]);
      foreach ($rmps as $rmp_idx => $rmp)
      {
        $ident = $rop_idx."_".$rmp_idx;

        if (count($rmps) > 1)
        {
          if ($rmp["reference"] != "")
          {
            list ($first, $rest)  = explode (" ", $rmp["reference"],2);
            $ref = $first;
            if ($rmp["link"] != "")
            {
              $ref = "<a href='".$rmp["link"]."'>".$first." et al</a>";
            }
          }
          echo "<h3>Measurement Method ".($rmp_idx + 1).": ".$ref."</h3>\n";
          echo "<div class='rmp'>\n";
        }

        //print_r($rmp);
        $rmp["ne2001_dm_limit"] = $rop["ne2001_dm_limit"];
        $der = calculate_derived_params ($rmp);

        #######################################################################
        # Measured Params
        echo "<table class='standard' cellpadding=5px width='100%'>\n";
        echo "<tr><th colspan=3>Measured Parameters</th></tr>\n";

         # now show the measured parameters
        renderField("RAJ<sup>a</sup>", $rop["raj"]."(".$pointing_error_secs.")", "[J2000]");
        renderField("DECJ<sup>a</sup>", $rop["decj"]."(".$pointing_error_arcsecs.")", "[J2000]");
        renderField("gl<sup>a</sup>", getQty ($rop["gl"], $pointing_error_degrees), "[deg]");
        renderField("gb<sup>a</sup>", getQty ($rop["gb"], $pointing_error_degrees), "[deg]");
        renderField("Positional uncertainty<sup>b</sup>", $rop["FWHM"], "[arcmin]");
        renderField("DM", getQty($rmp["dm"], $rmp["dm_error"]), "[cm<sup>-3</sup> pc]");
        renderField("S/N", $rmp["snr"]);
        renderField("W<sub>obs</sub>", getQtyErrors($rmp["width"] , $rmp["width_error_upper"], $rmp["width_error_lower"]), "[ms]");
        renderField("S<sub>peak,obs</sub>", $rmp["flux_prefix"].getQtyErrors($rmp["flux"] , $rmp["flux_error_upper"], $rmp["flux_error_lower"]), "[Jy]");
        renderField("F<sub>obs</sub>", $rmp["flux_prefix"].getQtyErrors($der["fluence"] , $der["fluence_error_upper"], $der["fluence_error_lower"]), "[Jy ms]");
        renderField("DM Index", getQty($rmp["dm_index"], $rmp["dm_index_error"]));
        renderField("Scattering Index", getQty($rmp["scattering_index"], $rmp["scattering_index_error"]));
        renderField("Scattering Time", getQty($rmp["scattering_time"], $rmp["scattering_time_error"]));
        renderField("Linear Poln Fraction", getQty($rmp["linear_poln_frac"], $rmp["linear_poln_frac_error"]));
        renderField("Circular Poln Fraction", getQty($rmp["circular_poln_frac"], $rmp["circular_poln_frac_error"]));
        renderField("Host Photometric Redshift", getQty($rmp["z_phot"], $rmp["z_phot_error"]));
        renderField("Host Spectroscopic Redshift", getQty($rmp["z_spec"], $rmp["z_spec_error"]));

        echo "</table>\n";

        $rmp_notes = getRadioMeasuredParamsNotes($link, $rmp["id"]);
        if (count($rmp_notes) > 0)
        {
          echo "<p>Notes:</p>\n";
          echo "<ul>\n";
          foreach ($rmp_notes as $note)
          {
            list ($day, $time) = explode(" ",$note["last_modified"]);
            $authorstamp = $note["author"]." ".$day;
            echo "<li>".$note["note"].". [".$authorstamp."]</li>\n";
          }
          echo "</ul>\n";
        }


        echo "<input type='hidden' name='fluence_field' id='".$ident."_fluence' value='".$der["fluence"]."'/>\n";
        #echo "<input type='hidden' name='fluence_error_upper_field' id='".$ident."_fluence_error_upper' value='".$der["fluence_error_upper"]."'/>\n";
        #echo "<input type='hidden' name='fluence_error_lower_field' id='".$ident."_fluence_error_lower' value='".$der["fluence_error_lower"]."'/>\n";
        echo "<input type='hidden' name='bandwidth_field' id='".$ident."_bandwidth' value='".$rop["bandwidth"]."'/>\n";

        echo "<input type='hidden' name='redshift_field' id='".$ident."_redshift' value='".$der["redshift"]."'/>\n";
        # echo "<input type='hidden' name='redshift_error_upper_field' id='".$ident."_redshift_error_upper' value='".$der["redshift_error_upper"]."'/>\n";
        # echo "<input type='hidden' name='redshift_error_lower_field' id='".$ident."_redshift_error_lower' value='".$der["redshift_error_lower"]."'/>\n";

        #######################################################################
        # Derived Params

        echo "<table class='standard' cellpadding=5px width='100%'>\n";
        echo "<tr><th colspan=3>Derived Parameters</th></tr>\n";

        renderField("DM<sub>galaxy</sub><sup>c</sup>", $rop["ne2001_dm_limit"], "[cm<sup>-3</sup> pc]");
        renderField("DM<sub>excess</sub>",  $der["dm_excess"], "[cm<sup>-3</sup> pc]");
        # renderField("DM<sub>excess</sub>",  getQtyErrors($der["dm_excess"] , $der["dm_excess_error_upper"], $der["dm_excess_error_lower"]),"[cm<sup>-3</sup> pc]");

        renderField("z<sup>d</sup>", $der["redshift"]);

        renderFieldID("D<sub>comoving</sub><sup>d</sup>", "&nbsp;", $ident."_dist_comoving", "[Gpc]");
        renderFieldID("D<sub>luminosity</sub><sup>d</sup>", "&nbsp;", $ident."_dist_luminosity", "[Gpc]");
        #renderField("D<sub>comoving</sub>", getQtyErrors("&nbsp;" , "&nbsp;", "&nbsp;", $ident."_dist_comoving"), "[Gpc]");
        #renderField("D<sub>luminosity</sub>", getQtyErrors("&nbsp;" , "&nbsp;", "&nbsp;", $ident."_dist_luminosity"), "[Gpc]");

        #renderField("z", getQtyErrors($der["redshift"] , $der["redshift_error_upper"], $der["redshift_error_lower"]));
        renderFieldID("Energy<sup>d</sup>", "&nbsp;", $ident."_energy", "[10<sup>32</sup> J]");
        #renderField("Energy", getQtyErrors("&nbsp;" , "&nbsp;", "&nbsp;", $ident."_energy"), "[10<sup>32</sup> J]");

        echo "</table>\n";

        $js .= "  updateDerived('".$rop_idx."_".$rmp_idx."');\n";

        if (count ($rmps) > 1)
          echo "</div>\n";  // derived params
      }

      echo "</td>\n";
      echo "<td width='50%'>\n";

      #######################################################################
      # Data Products for Observation
      echo "<table class='standard' cellpadding=5px width='100%'>\n";
      echo "<tr><th>Data Products</th></tr>\n";

      $images = getRadioImages($link, $rop["id"]);

      $raw_data_link = "Not Available";
      if (($frb["raw_data_url"] != "") && ($frb["raw_data_url"] != ""))
        $raw_data_link = "<a href='".$frb["raw_data_url"]."'>Download</a>";

      foreach ($images as $img_idx => $img)
      {
        echo "<tr><td colspan=3 align=center>";
        echo $img["title"]."<br/>\n";
        $url = "view_image.php?id=".$img["id"];
        echo "<a href='".$url."'><img src='".$url."' width='340px'/></a><br/>\n";
        echo "<font size='-1'>".$img["caption"]."</font>\n";
        echo "</td></tr>\n";
      }

      echo "</table>\n";

      # Close loop
      echo "</td>\n";
      echo "</tr>\n";
      echo "</table>\n";

      if (count($radio_obs_params) > 1)
      {
        echo "</div>\n";  //  obs_param
      }
    }
  }
  echo "<script type='text/javascript'>\n";
  echo "function updateCosmologicalParams()\n";
  echo "{\n";
  echo $js;
  echo "}\n";

  echo "document.getElementsByTagName('body')[0].onload=updateCosmologicalParams();\n";
  echo "</script>\n";

  echo "<hr/>\n";
  echo "[a] These are the best available position measurements for the FRB. In the case where the burst has not been localised precisely, these are the coordinates of the centre of the detection beam. In this case, errors refer to the pointing accuracy of the given telescope.<br/>\n";
  echo "[b] This parameter give a best guess as to the positional uncertainty of the FRB. In the case of single-dish, single-beam detections this is the full-width half-maximum of the detection beam.<br/>\n";
  echo "[c] Galactic DM contribution derived from <a href='http://adsabs.harvard.edu/full/2004ASPC..317..211C'>NE2001: A New Model for the Galactic Electron Density and
its Fluctuations</a>.<br/>\n";
  echo "[d] Model dependent errors on Galactic DM contribution due to [c] are not included in these parameters.<br/>\n";
}
else
{
  echo "<p>ERROR: could not find FRBCAT record</p>\n";
}
?>

<!-- ############  END CONTENT    ############# -->
<!-- ########################################## -->

<!--#include virtual="/siteheaders/footer.html"-->
