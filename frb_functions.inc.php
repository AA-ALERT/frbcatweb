<?PHP

if (!$_FRBS_FUNCTIONS_INC_PHP)
{
  $_FRBS_FUNCTIONS_INC_PHP = 1;

  define (DEBUG, false);

function db_connect()
{
  $fptr = fopen ("db_secrets.txt", "r");

  $host = rtrim(fgets($fptr));
  $user = rtrim(fgets($fptr));
  $pass = rtrim(fgets($fptr));
  $DB   = rtrim(fgets($fptr));

  fclose($fptr);

  $resourceid = mysql_connect("$host", "$user", "$pass");
  if (mysql_select_db($DB)) {
    return $resourceid;
  } else {
    return false;
  }
}

function get_flat_table ($link)
{

  $qry = "select f.name, 
       o.telescope, 
       o.type,
       o.utc,
       rop.beam,
       rop.receiver,
       rop.backend,
       rop.bandwidth,
       rop.raj,
       rop.decj,
       rop.pointing_error,
       rop.FWHM,
       rop.sampling_time,
       rop.bandwidth,
       rop.centre_frequency,
       rop.bits_per_sample,
       rop.gain,
       rop.tsys,
       rop.ne2001_dm_limit,
       rmp.dm, 
       rmp.dm_error, 
       rmp.snr, 
       rmp.width, 
       rmp.width_error_lower, 
       rmp.width_error_upper, 
       rmp.flux,
       rmp.flux_error_lower, 
       rmp.flux_error_upper,
       rmp.dm_index,
       rmp.dm_index_error,
       rmp.scattering_index,
       rmp.scattering_index_error,
       rmp.scattering_time,
       rmp.scattering_time_error,
       rmp.linear_poln_frac,
       rmp.linear_poln_frac_error,
       rmp.circular_poln_frac,
       rmp.circular_poln_frac_error,
       rmp.z_phot,
       rmp.z_phot_error,
       rmp.z_spec,
       rmp.z_spec_error,
       r0.reference
from frbs as f 
join refs as r0 
on (f.reference_id = r0.id)
join observations as o 
on (o.frb_id = f.id)
join radio_obs_params as rop 
on (rop.obs_id=o.id)
join radio_measured_params as rmp
on (rmp.obs_params_id = rop.id) where f.private=0 order by f.name";

  $res = mysql_query($qry, $link);
  $records = array();
  for ($i=0; $i<mysql_numrows($res); $i++)
  {
    $records[$i] = mysql_fetch_assoc($res);
  }

  return $records;
  
}

function calculate_derived_params ($frb)
{
  $fluence = "";
  $fluence_error_upper = "";
  $fluence_error_lower = "";

  if ($frb["width"] != "" && $frb["flux"] != "")
  {
    $width = floatval($frb["width"]);
    $flux = floatval($frb["flux"]);
    if (is_float($width) && is_float($flux))
    {
      $fluence = $width * $flux;
      $frb["fluence"] = $frb["width"] * $frb["flux"];

      if ($frb["flux_error_upper"] != "" && $frb["width_error_upper"] != "" && $frb["flux_error_lower"] != "" && $frb["width_error_lower"] != "")
      {
        $flux_error_upper = floatval($frb["flux_error_upper"]);
        $flux_error_lower = floatval($frb["flux_error_lower"]);
        $width_error_upper = floatval($frb["width_error_upper"]);
        $width_error_lower = floatval($frb["width_error_lower"]);

        if (is_float($width_error_upper) && is_float($width_error_lower))
        {
          $fluence_error_upper = ($flux + $flux_error_upper) * ($width + $width_error_upper);
          $fluence_error_lower = ($flux - $flux_error_lower) * ($width - $width_error_lower);
          $frb["fluence_error_upper"] = $fluence_error_upper - $fluence;
          $frb["fluence_error_lower"] = abs($fluence_error_lower - $fluence);
        }
      }
    }
  }

  // if we have DM information
  if ($frb["dm"] != "" && $frb["ne2001_dm_limit"] != "")
  {
    $dm = floatval($frb["dm"]);
    $ne2001_dm_limit = floatval($frb["ne2001_dm_limit"]);

    if (is_float($dm) && is_float($ne2001_dm_limit))
    {
      $dm_excess = $dm - $ne2001_dm_limit;
      $dm_excess_error_upper = $dm - (0.5 * $ne2001_dm_limit);
      $dm_excess_error_lower = $dm - (1.5 * $ne2001_dm_limit);

      #echo "dm_excess=$dm_excess dm=$dm ne2001_dm_limit=$ne2001_dm_limit dm_excess_error_upper=$dm_excess_error_upper dm_excess_error_lower=$dm_excess_error_lower<BR>\n";

      $redshift = $dm_excess / 1200.0;
      $redshift_error_upper = $dm_excess_error_upper / 1200.0;
      $redshift_error_lower = $dm_excess_error_lower / 1200.0;

      #echo "redshift=$redshift redshift_error_upper=$redshift_error_upper redshift_error_lower=$redshift_error_lower<BR>\n";

      $frb["dm_excess"] = $dm_excess;
      $frb["dm_excess_error_upper"] = number_format($dm_excess_error_upper - $dm,5);
      $frb["dm_excess_error_lower"] = number_format(abs($dm_excess_error_lower - $dm),5);
      $frb["redshift"] = number_format($redshift,2);
      $frb["redshift_error_upper"] = number_format(($redshift_error_upper - $redshift),5);
      $frb["redshift_error_lower"] = number_format(abs($redshift_error_lower - $redshift),5);

      // depends on redshift
      if ($frb["dist_comoving"] != "")
      {
        $dist_comoving = floatval($frb["dist_comoving"]);
        if (is_float($dist_comoving))
        {
          $dist_luminosity = $dist_comoving * (1 + $redshift);
          $frb["dist_luminosity"] = number_format ($dist_luminosity,2);

          if ($frb["bandwidth"] != "")
          {
            $bandwidth = floatval($frb["bandwidth"]);
            if (is_float($bandwidth) && is_float($fluence))
            {
              $energy = $fluence  * pow(10,-26) * 0.001 * pow(($dist_luminosity * 3.08567758 * pow(10,25)),2) * $bandwidth * pow(10,6) * (1 + $redshfit);
              $energy /= pow(10,32);
              $frb["energy"] = number_format ($energy, 2);
            }
          }

          if ($frb["dist_comoving_error_upper"] != "" && $frb["dist_comoving_error_lower"] != "")
          {
            $dist_comoving_error_upper = floatval($frb["dist_comoving_error_upper"]);
            $dist_comoving_error_lower = floatval($frb["dist_comoving_error_lower"]);

            if (is_float($dist_comoving_error_upper) && is_float($dist_comoving_error_lower))
            {
              $dist_luminosity_error_upper = ($dist_comoving + $dist_comoving_error_upper) * (1 + $redshift_error_upper);
              $dist_luminosity_error_lower = ($dist_comoving - $dist_comoving_error_lower) * (1 + $redshift_error_lower);
              $frb["dist_luminosity_error_upper"] = number_format(($dist_luminosity_error_upper - $dist_luminosity),2);
              $frb["dist_luminosity_error_lower"] = number_format(abs($dist_luminosity_error_lower - $dist_luminosity),2);

              if ($frb["bandwidth"] != "")
              {
                $bandwidth = floatval($frb["bandwidth"]);
                if (is_float($bandwidth) && is_float($fluence))
                {
                  $energy_error_upper = $fluence_error_upper * pow(10,-26) * 0.001 * pow(($dist_luminosity_error_upper * 3.08567758 * pow(10,25)),2) * $bandwidth * pow(10,6) * (1 + $redshfit_error_upper);
                  $energy_error_lower = $fluence_error_lower * pow(10,-26) * 0.001 * pow(($dist_luminosity_error_lower * 3.08567758 * pow(10,25)),2) * $bandwidth * pow(10,6) * (1 + $redshfit_error_lower);

                  // unit conversion
                  $energy_error_upper /= pow(10,32);
                  $energy_error_lower /= pow(10,32);

                  $frb["energy_error_upper"] = number_format(($energy_error_upper - $energy),2);
                  $frb["energy_error_lower"] = number_format(abs($energy_error_lower - $energy),2);
                }
              }
            }
          }

          if ($frb["bandwidth"] != "")
          {
            $bandwidth = floatval($frb["bandwidth"]);
            if (is_float($bandwidth) && is_float($fluence))
            {
              $energy = $fluence  * pow(10,-26) * 0.001 * pow(($dist_luminosity * 3.08567758 * pow(10,25)),2) * $bandwidth * pow(10,6) * (1 + $redshfit);
              $energy /= pow(10,32);
              $frb["energy"] = number_format ($energy, 2);
            }
          }
        }
      }
    }
  }

  return $frb;

}

function getDBFields()
{
  $struct = getDBStructure();
  $fields = array();

  for ($i=0; $i<count($struct); $i++)
  {
    $i_keys = array_keys($struct[$i]);
    $fields[$i] = $i_keys[0];
  }
  return $fields;
}

function getSelectList ($link, $name) 
{
  $list = array();
  $res = 0;

  if (($name == "reference") || ($name == "reference_id"))
  {
    $qry = "select id, reference from frb_references";
    $res = mysql_query($qry, $link);
    $name = "reference";
  }

  if ($name == "reference_url")
  {
    $qry = "select id, url from frb_references";
    $res = mysql_query($qry, $link);
    $name = "url";
  }

  if ($res)
  {
    for ($i=0; $i<mysql_numrows($res); $i++)
    {
      $list[mysql_result($res, $i, "id")] = mysql_result($res, $i, $name);
    }
  }

  return $list;
}

function getObservations ($link, $frb_id)
{
  $observations = array();
  $qry = "select f.name, o.utc, o.telescope, o.type, o.id, o.data_link from frbs as f join observations as o on (o.frb_id = f.id) where o.frb_id = '".$frb_id."' and f.private = 0";
  $res = mysql_query($qry, $link);

  for ($i=0; $i<mysql_numrows($res); $i++)
  {
    $observations[$i] = mysql_fetch_assoc($res);
  }

  return $observations;
}

function getRadioObsParams ($link, $obs_id)
{
  $records = array();

  $qry = "select rop.*, r0.reference, r0.link from observations as o join refs as r0 on (o.reference_id = r0.id) join radio_obs_params as rop on (rop.obs_id=o.id) where o.id=".$obs_id;

  $res = mysql_query($qry, $link);
  for ($i=0; $i<mysql_numrows($res); $i++)
  {
    $records[$i] = mysql_fetch_assoc($res);
  }

  return $records;
}

function getRadioObsParamsNotes($link, $id)
{
  $notes = array();
  $qry = "select * from radio_obs_params_notes where radio_obs_param_id=".$id;
  $res = mysql_query($qry, $link);

  for ($i=0; $i<mysql_numrows($res); $i++)
  {
    $notes[$i] = mysql_fetch_assoc($res);
  }
  return $notes;
}


function getRadioMeasuredParams ($link, $rop_id)
{
  $records = array();

  $qry = "select rmp.*, refs.reference, refs.link from radio_measured_params as rmp ".
          "left join refs on (rmp.reference_id = refs.id) ".
          "where rmp.obs_params_id = '".$rop_id."'";
  $res = mysql_query($qry, $link);
  for ($i=0; $i<mysql_numrows($res); $i++)
  {
    $records[$i] = mysql_fetch_assoc($res);
  }
  return $records;
}

function getRadioMeasuredParamsNotes($link, $id)
{
  $notes = array();
  $qry = "select * from radio_measured_params_notes where radio_measured_param_id=".$id;
  $res = mysql_query($qry, $link);

  for ($i=0; $i<mysql_numrows($res); $i++)
  {
    $notes[$i] = mysql_fetch_assoc($res);
  }
  return $notes;
}


function getRadioImages($link, $rop_id)
{
  $records = array();

  $qry = "select ri.id, ri.title, ri.caption from radio_images as ri where ri.radio_obs_params_id = '".$rop_id."'";
  $res = mysql_query($qry, $link);
  for ($i=0; $i<mysql_numrows($res); $i++)
  {
    $records[$i] = mysql_fetch_assoc($res);
  }
  return $records;
}

function getPublicRadioImage($link, $image_id)
{
  # need to be sure that the image is public!
  $qry = "select ri.image from radio_images as ri ".
         "left join frbs as f on (ri.frb_id = f.id ) ".
         "where f.private = 0 and ri.id= '".$image_id."'";
  $res = mysql_query($qry, $link);
  if (mysql_numrows($res) == 1)
  {
    $record = mysql_fetch_assoc($res);
    return $record["image"];
  }
  else
    return 0;
}

function getFRB($link, $id)
{
  $frb = array();
  $qry = "select f.name, f.utc, o.telescope, rop.*, r0.reference from frbs as f ".
         "join observations as o on (o.frb_id = f.id) ".
         "join refs as r0 on (o.reference_id = r0.id) ".
         "join radio_obs_params as rop on (rop.obs_id=o.id) ".
         "where f.private = 0 and f.id=".$id;
  $res = mysql_query($qry, $link);

  for ($i=0; $i<mysql_numrows($res); $i++)
  {
    $frb[$i] = mysql_fetch_assoc($res);
  }

  return $frb;
}

function getFRBNotes($link, $id)
{
  $notes = array();
  $qry = "select * from frbs_notes where frb_id=".$id;
  $res = mysql_query($qry, $link);

  for ($i=0; $i<mysql_numrows($res); $i++)
  {
    $notes[$i] = mysql_fetch_assoc($res);
  }
  return $notes;
}


function getObs($link, $id)
{
  $obs = array();
  $qry = "select f.name,f.utc,rmp.*,r0.reference from frbs as f ".
         "join observations as o on (o.frb_id = f.id) ".
         "join radio_obs_params as rop on (rop.obs_id=o.id) ".
         "join radio_measured_params as rmp on (rmp.obs_params_id = rop.id) ".
         "join refs as r0 on (rmp.reference_id = r0.id) ".
         "where f.private=0 and o.id=".$id;
  $res = mysql_query($qry, $link);
  if (mysql_numrows($res) == 1)
  {
    $obs = mysql_fetch_assoc($res);
  }
  return $obs;
}

function getObsNotes($link, $observation_id)
{
  $notes = array();
  $qry = "select * from observations_notes where observation_id=".$observation_id;
  $res = mysql_query($qry, $link);
  for ($i=0; $i<mysql_numrows($res); $i++)
  { 
    $notes[$i] = mysql_fetch_assoc($res);
  }
  return $notes;

}

function readFRBs ($link)
{
  $frbs = array();

/*
  $qry = "select f.id, f.name, o.telescope, TRUNCATE(rop.gl,3) as gl, 
      TRUNCATE(rop.gb,3) as gb, TRUNCATE(rop.FWHM/60.0,2) as FWHM, 
      rop.beam, rmp.dm, rmp.dm_error, rmp.snr, rmp.width, 
      rmp.width_error_lower, rmp.width_error_upper, rmp.flux,
      rmp.flux_prefix,rmp.flux_error_lower, rmp.flux_error_upper,r0.* 
from frbs as f 
join refs as r0 
on (f.reference_id = r0.id)
join observations as o 
on (o.frb_id = f.id)
join radio_obs_params as rop 
on (rop.obs_id=o.id)
join (
      select dm,dm_error,snr,width,width_error_lower,
             width_error_upper,flux,flux_prefix,flux_error_lower,
             flux_error_upper,MIN(rank) as rank,obs_params_id
      from radio_measured_params as rmp 
      join frbs on (rmp.frb_id=frbs.id) 
      group by frbs.id
      ) rmp 
on (rmp.obs_params_id = rop.id)
where f.private=0 
order by f.name;";
*/

  $qry = "select f.id, f.name, o.telescope, TRUNCATE(rop.gl,3) as gl, 
      TRUNCATE(rop.gb,3) as gb, TRUNCATE(rop.FWHM/60.0,2) as FWHM, 
      rop.beam, rmp.dm, rmp.dm_error, rmp.snr, rmp.width, 
      rmp.width_error_lower, rmp.width_error_upper, rmp.flux,
      rmp.flux_prefix, rmp.flux_error_lower, rmp.flux_error_upper,r0.* 
from frbs as f 
join refs as r0 
on (f.reference_id = r0.id)
join observations as o 
on (o.frb_id = f.id)
join radio_obs_params as rop 
on (rop.obs_id=o.id)
join radio_measured_params as rmp on (rmp.obs_params_id = rop.id)
inner join
(
select MIN(rank) as minrank,obs_params_id
      from radio_measured_params
      group by frb_id
) rmp_b on (rmp_b.obs_params_id = rmp.obs_params_id) and (rmp_b.minrank=rmp.rank)
where f.private=0
order by f.name;";

  $res = mysql_query($qry, $link);
  if (!$res) {
    return $frbs;
  }

  $nrows = mysql_numrows($res);

  # get the field names
  $fields = array();
  if ($nrows > 0) {
    $values = mysql_fetch_row($res);

    # assume field 0 is the id
    for ($i=1; $i<mysql_num_fields($res); $i++)
      $fields[$i] = mysql_field_name($res, $i);

    mysql_data_seek($res, 0);
  }

  for ($i=0; $i<$nrows; $i++) {
    $values = mysql_fetch_row($res);
    $id = $values[0];

    if (!array_key_exists($id, $frbs))
      $frbs[$id] = array();

    for ($j=1; $j<mysql_num_fields($res); $j++) {

      $frbs[$id][$fields[$j]] = $values[$j];

    }
  }

  return $frbs;
}
function readFRB ($link, $id)
{
  $frbs = array();

  $qry = "select f.name, o.telescope, rop.*, r0.reference from frbs as f join observations as o on (o.frb_id = f.id) join refs as r0 on (o.reference_id = r0.id) join radio_obs_params as rop on (rop.obs_id=o.id) where f.private = 0 and f.id=".$id;

  $res = mysql_query($qry, $link);
  if (!$res) {
    return $frbs;
  }

  $nrows = mysql_numrows($res);

  # get the field names
  $fields = array();
  if ($nrows > 0) {
    $values = mysql_fetch_row($res);

    # assume field 0 is the id
    for ($i=1; $i<mysql_num_fields($res); $i++)
      $fields[$i] = mysql_field_name($res, $i);

    mysql_data_seek($res, 0);
  }

  for ($i=0; $i<$nrows; $i++) {
    $values = mysql_fetch_row($res);
    $id = $values[0];

    if (!array_key_exists($id, $frbs))
      $frbs[$id] = array();

    for ($j=1; $j<mysql_num_fields($res); $j++) {

      $frbs[$id][$fields[$j]] = $values[$j];

    }
  }

  return $frbs;
}


function readReferences($link)
{
  $refs = array();

  $qry = "select * from frb_references order by id";
  $res = mysql_query($qry, $link);

  if (!$res) {
    return $refs;
  }

  $nrows = mysql_numrows($res);

  # get the field names
  $fields = array();
  if ($nrows > 0) {
    $values = mysql_fetch_row($res);

    # assume field 0 is the id
    for ($i=1; $i<mysql_num_fields($res); $i++)
      $fields[$i] = mysql_field_name($res, $i);

    mysql_data_seek($res, 0);
  }

  for ($i=0; $i<$nrows; $i++) {
    $values = mysql_fetch_row($res);
    $id = $values[0];

    if (!array_key_exists($id, $refs))
      $refs[$id] = array();

    for ($j=1; $j<mysql_num_fields($res); $j++) {

      $refs[$id][$fields[$j]] = $values[$j];

    }
  }

  return $refs;
}

function getQty($qty, $err)
{
  // work out how many decimal places there are in the err
  if ($err == "")
    return $qty;
  else
  {
    $ndeci = floor(log10($err));

    if ($ndeci < 0)
    { 
      $post = -1 * $ndeci;
      $pre  = strlen($qty) + 1 + $post;
      $format = "%".$pre.".".$post."f";
      $formatted = sprintf($format, $qty);
      $err_formatted = sprintf("%d", $err * pow(10, -1 * $ndeci));
      //return $qty."(".$err.") -> ndeci=".$ndeci." pre=".$pre." post=".$post." format=".$format." ".$formatted."(".$err_formatted.")";
      //return $qty."(".$err.") -> ".$formatted."(".$err_formatted.")";
      return $formatted."(".$err_formatted.")";
    }
    else
    {
      return $qty."(".$err.")";
    }
  }
}

function renderQty ($qty, $err)
{
  echo getQty($qty, $err);
}

function renderQtyErrors($qty, $pos, $neg, $id="")
{
  echo getQtyErrors($qty, $pos, $neg, $id);
}

function getQtyErrors ($qty, $pos, $neg, $id="")
{
  $qty_formatted = number_format($qty,2);
  $neg_formatted = number_format($neg,2);
  $pos_formatted = number_format($pos,2);

  //$qty_formatted = $qty;
  //$neg_formatted = $neg;
  //$pos_formatted = $pos;

  if (($pos != "") && ($neg != ""))
    if ($id == "")
      return "<span>".$qty_formatted."</span><span class='subsup'><sup>+".$pos_formatted."</sup><sub>-".$neg_formatted."</sub></span>";
    else
      return "<span id='".$id."'>".$qty_formatted."</span><span class='subsup'><sup><span class='suppy' id='".$id."_error_upper'>+".$pos_formatted."</span></sup><sub><span class='subby' id='".$id."_error_lower'>-".$neg_formatted."</span></sub></span>";
  else if (($pos == "") && ($$neg == ""))
    return $qty_formatted;
  else if ($pos != "")
    return $qty_formatted." +".$pos_formatted;
  else if ($neg != "")
    return $qty_formatted." -".$neg_formatted;
  else
    return "error";
}

#
# Adds a new frb to the mysql database and to the CAS google calendar
#
function addFRB ($link, $new)
{
  $errors = array();

  # check that all the required values have been set
  $errors = checkFRBDetails ($new, $link);

  if (count($errors) == 0)
  {
    # get list of database fields
    $fields = getDBFields();

    $qry = "insert into frbs (";
    $qry .= implode(", ",$fields);
    $qry .= ") values (";

    $qry .= "'".$new[$fields[0]]."'";

    for ($j=1; $j<count($fields); $j++) {
      $qry .= ",'".$new[$fields[$j]]."'";
    }
    $qry .= ")";

    # insert the new record into our database
    $res = mysql_query($qry, $link);
    if (!$res)
    {
      $errors["mysql"] = mysql_error();
      echo "<p>Failed to add FRB</p>\n";
    }
  }

  return $errors;
}

function editFRB($link, $new)
{
  $errors = array();

  $id = $new["id"];
  unset ($new["id"]);

  $qry = "select * from frbs where id = ".$id;
  $res = mysql_query($qry, $link);
  if (!$res)
  {
    $errors["mysql"] = mysql_error();
    return $errors;
  }

  if (mysql_numrows($res) != 1)
  {
    $errors["mysql"] = "Found more than 1 matching row";
    return $errors;
  }

  # prepare mysql update query
  $keys = array_keys($new);

  $qry = "update frbs set";

  $qry .= " ".$keys[0]." = '".$new[$keys[0]]."'";
  for ($i=1; $i<count($keys); $i++)
  {
    if ($new[$keys[$i]] == "")
      $qry .= ", ".$keys[$i]." = NULL";
    else
      $qry .= ", ".$keys[$i]." = '".$new[$keys[$i]]."'";
  }
  $qry .= " where id = ".$id;

  $res = mysql_query($qry, $link);
  if (!$res)
    $errors["mysql"] = mysql_error();
  return $errors;
}

function deleteFRB($link, $id_to_delete)
{
  $errors = array();

  $qry = "select * from frbs where id = ".$id_to_delete;
  $res = mysql_query($qry, $link);
  if (!$res)
  {
    $errors["mysql"] = mysql_error();
    return $errors;
  }

  if (mysql_num_rows($res) != 1)
  {
    $errors["mysql"] = "found more than 1 record with ID=".$id_to_delete;
    return $errors;
  }

  $qry = "delete from frbs where id = ".$id_to_delete;
  mysql_query($qry, $link);

  return $errors;
}

function checkFRBDetails($new, $link) 
{
  $errors = array();
  $struct = getDBStructure();

  return $errors;
}

}// _FRBS_FUNCTIONS_INC_PHP
