<!--#set var="title" value="CAS - Pulsar - FRB Catalogue" -->
<!--#set var="siteid" value="883" -->
<!--#set var="menuid" value="16578" -->
<!--#set var="maintainer_name" value="Andrew Jameson" -->
<!--#set var="maintainer_uname" value="ajameson" -->
<!--#set var="additional_head" value="/pulsar/frbcat/additional_head.html" -->
<!--#include virtual="/siteheaders/header.html" -->

<!-- ########################################## -->
<!-- ############  START CONTENT  ############# -->

<h1>FRB Catalogue</h1>

<p>
This catalogue contains up to date information for the published population of Fast Radio Bursts (FRBs). This site is
maintained by the FRBcat team and is updated as new sources are published or refined numbers become available.
Information for each burst is divided into two categories: intrinsic properties measured using the available data, and
derived parameters produced using a model. The intrinsic parameters should be taken as lower limits, as the position within
the telescope beam is uncertain. Models used in this analysis are the NE2001 Galactic electron distribution (Cordes &amp; 
Lazio, 2002), and the Cosmology Calculator (Wright, 2006). 
</p>

<p>
You may use the data presented in this catalogue for publications; however, we ask that you cite the paper, when available
(Petroff et al., 2016) and provide the url (http://www.astronomy.swin.edu.au/pulsar/frbcat/).
</p>

<?
  include("frb_functions.inc.php");

  $link = db_connect();
  $frbs = readFRBs ($link);
?>

<h2>Catalogue Version 1.0</h2>
<table class='standard' cellspacing='0' summary='FRB Intrinsic Properties'>

<tr>
  <th>Event</th>
  <th>Telescope</th>
  <th>gl [deg]</th>
  <th>gb [deg]</th>
  <th>FWHM [deg]</th>
  <th>DM [cm<sup>-3</sup> pc]</th>
  <th>S/N</th>
  <th>W<sub>obs</sub> [ms]</th>
  <th>S<sub>peak,obs</sub> [Jy]</th>
  <th>F<sub>obs</sub> [Jy ms]</th>
  <th>Ref</th>
</tr>

<?
$keys = array_keys($frbs);
$refs = array();
$ref_idx = 0;

foreach ($keys as $id)
{
  $frb = calculate_derived_params($frbs[$id]);

  echo "<tr>\n";
  echo "<td><a href='view.php?id=".$id."'>".$frb["name"]."</a></td>\n";
  echo "<td>".$frb["telescope"]."</td>\n";

  echo "<td>".$frb["gl"]."</td>\n";
  echo "<td>".$frb["gb"]."</td>\n";
  echo "<td>".$frb["FWHM"]."</td>\n";

  echo "<td>";
  renderQty ($frb["dm"], $frb["dm_error"]);
  echo "</td>\n";

  echo "<td>".$frb["snr"]."</td>\n";

  echo "<td>";
  renderQtyErrors($frb["width"] , $frb["width_error_upper"], $frb["width_error_lower"]);
  echo "</td>\n";

  echo "<td>";
  echo $frb["flux_prefix"];
  renderQtyErrors($frb["flux"] , $frb["flux_error_upper"], $frb["flux_error_lower"]);
  echo "</td>\n";

  echo "<td>";
  echo $frb["flux_prefix"];
  renderQtyErrors($frb["fluence"] , $frb["fluence_error_upper"], $frb["fluence_error_lower"]);
  echo "</td>\n";

  $in_array = false;
  foreach ($refs as $idx => $ref)
  {
    if (strcmp($ref["reference"], $frb["reference"]) == 0)
    {
      $in_array = true;
      $this_idx = $idx;
    }
  }

  if (!$in_array)
  {
    $ref_idx++;
    $refs[$ref_idx] = array ("reference" => $frb["reference"], 
                             "link" => $frb["link"], 
                             "description" => $frb["description"]);
    $this_idx = $ref_idx;
  }

  echo "<td><a href='".$frb["link"]."'>".$this_idx."</a></td>\n";
}

?>

</table>

<p>The full catalogue can be viewed in <a href='table.php?format=html'>tabular format</a> or downloaded as
<ul>
  <li><a href='table.php?format=votable&sep=comma'>VO Table (experimental)</a></li>
  <li><a href='table.php?format=text&sep=comma'>CSV</a></li>
  <li><a href='table.php?format=text&sep=tab'>Tab Delimited</a></li>
</ul>
</p>


<h2>Version Notes</h2>

<p>Version 1.0: This catalogue contains all currently available FRBs with their publication values and re-analysis described in Petroff et al.
(2016). New FRBs will be added to the catalogue as they become available but will not precipitate a new version. Minor corrections to the
catalogue may be performed without new version notes. New versions will be released as new analysis is performed on the sample, new parameters
are added to the catalogue, or new cosmological/progenitor considerations become necessary.</p>

<h2>References</h2>
<table class='standard' cellspacing='0' summary='References'>

<tr>
  <th>ID</th>
  <th>Title</th>
  <th>Reference</th>
</tr>

<?
$keys = array_keys($refs);
foreach ($keys as $idx)
{
  $ref = $refs[$idx];

  echo "<tr>\n";
  echo "<td>".$idx."</td>\n";
  echo "<td>".$ref["description"]."</td>\n";
  if ($ref["link"] == "")
    echo "<td>".$ref["reference"]."</td>\n";
  else
    echo "<td><a href='".$ref["link"]."'>".$ref["reference"]."</a></td>\n";
  echo "</tr>\n";
}

?>
</table>

<!-- ############  END CONTENT    ############# -->
<!-- ########################################## -->

<!--#include virtual="/siteheaders/footer.html"-->
