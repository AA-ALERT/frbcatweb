<?php
include("frb_functions.inc.php");

$id = "all";
$link = db_connect();
$frbs = get_flat_table($link);
$sep = "";
$format = "html";
if (isset($_GET["format"]))
{
  if ($_GET["format"] == "votable")
  {
    $format = "votable";
    $sep = ",";
  }
  else if ($_GET["format"] == "text")
  {
    $format = "text";
    $sep = ",";
    if (isset($_GET["sep"]))
    {
      if ($_GET["sep"] == "comma")
      {
        $sep = ",";
      }
      else if ($_GET["sep"] == "tab")
      {
        $sep = "\t";
      }
      else
      {
        echo "ERROR: unrecognized separator<BR>\n";
        exit;
      }
    }
  }
}
else{
  echo "ERROR: unspecified separator<BR>\n";
  exit;
}

$output = "";
if ($format == "html")
  $output .= "<table cellpadding='5px' style='border: 1px; border-style: solid solid solid sold;'>\n";

if ($format == "html")
  $output .= "<tr>\n";

$fields = array("name" => "Name",
                "telescope" => "Telescope",
                "type" => "Type",
                "utc" => "UTC",
                "beam" => "Beam",
                "receiver" => "Receiver",
                "backend" => "Backend",
                "raj" => "RAJ",
                "decj" => "DECJ",
                "pointing_error" => "Pointing Error",
                "FWHM" => "FWHM",
                "sampling_time" => "Sampling Time",
                "bandwidth" => "Bandwidth",
                "centre_frequency" => "Centre Frequency",
                "bits_per_sample" => "Bits per Sample",
                "gain" => "Gain",
                "tsys" => "System Temperature",
                "ne2001_dm_limit" => "NE2001 DM Limit",
                "dm" => "DM",
                "dm_error" => "DM error",
                "snr" => "SNR",
                "width" => "Width",
                "width_error_lower" => "Width error lower",
                "width_error_upper" => "Width error upper",
                "flux" => "Flux",
                "flux_error_lower" => "Flux error lower",
                "flux_error_upper" => "Flux error upper",
                "dm_index" => "DM Index",
                "dm_index_error" => "DM Index Error",
                "scattering_index" => "Scattering Index",
                "scattering_index_error" => "Scattering Index Error",
                "scattering_time" => "Scattering Time",
                "scattering_time_error" => "Scattering Time Error",
                "linear_poln_frac" => "Linear Polarization Fraction",
                "linear_poln_frac_error" => "Linear Polarization Fraction Error",
                "circular_poln_frac" => "Circular Polarization Fraction",
                "circular_poln_frac_error" => "Circular Polarization Fraction Error",
                "z_phot" => "Photometric Redshift",
                "z_phot_error" => "Photometric Redshift Error",
                "z_spec" => "Spectroscopic Redshift",
                "z_spec_error" => "Spectroscopic Redshift Error",
                "reference" => "Reference"
               );

foreach ($fields as $key => $value)
{
  $output .= renderHeaderCell($value, $format, $sep);
}
if ($format == "html")
{
  $output .= "</tr>\n";
}
else
{
  $output = rtrim($output, $sep)."\n";
}

$frbkeys = array_keys($frbs);

foreach ($frbkeys as $id)
{
  $frb = $frbs[$id];

  if ($format == "html")
    $output .= "<tr>\n";

  foreach ($fields as $key => $value)
  {
    $output .= renderDataCell($frb[$key], $format, $sep);
  }

  if ($format == "html")
  {
    $output .= "</tr>\n";
  }
  else
  {
    $output = rtrim($output, $sep)."\n";
  }
}

$file_time = gmdate("Y-m-d");

header("Cache-Control: no-cache, must-revalidate");             // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");   // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
header("Pragma: no-cache");                                     // HTTP/1.0

if ($format == "html")
{
  $output .= "</table>\n";

  $output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n".
            "<html>\n".
            "<body>\n".
            $output.
            "</body>\n".
            "</html>\n";
}
else if ($format == "votable")
{
  $csv_file = tempnam(sys_get_temp_dir(), 'frbcat');
  $xml_file = tempnam(sys_get_temp_dir(), 'frbcat');
  $fptr = @fopen ($csv_file, "w");
  fwrite($fptr, $output);
  fclose($fptr);

  $cmd = "./scripts/run_csv_to_vo_table.sh ".$csv_file." ".$xml_file;

  $line = exec($cmd, $lines, $rval);
  if (DEBUG)
  {
    foreach ($lines as $line)
      echo $line."<br>\n";
  }
  unlink ($csv_file);

  header ("Content-Type: text/xml");
  header ("Content-Disposition: attachment; filename=frbcat_".$file_time.".votable");
  readfile($xml_file);
  $output = "";
  unlink($xml_file);

}
else
{
  if ($sep == ",")
  {
    header ("Content-Type: text/csv");
    header ("Content-Disposition: attachment; filename=frbcat_".$file_time.".csv");
  }
  else if ($sep == "\t")
  {
    header ("Content-Type: text/plain");
    header ("Content-Disposition: attachment; filename=frbcat_".$file_time.".txt");
  }
}

echo $output;
return 0;


function renderHeaderCell ($qty, $format, $sep)
{
  if ($format == "html")
    return "<td style='background-color: #BBB;'><b>".$qty."</b></td>";
  else if ($format == "text" || $format == "votable")
  {
    if (strpos($qty, $sep) === false)
      return $qty.$sep;
    else
      return "\"".$qty."\"".$sep;
  }
}

function renderDataCell ($qty, $format, $sep)
{
  if ($format == "html")
    return "<td>".$qty."</td>";
  else if ($format == "text" || $format == "votable")
  {
    if (strpos($qty, $sep) === false)
      return $qty.$sep;
    else
      return "\"".$qty."\"".$sep;
  }
}
?>
