// by Ned Wright
// 25 Jul 1999
// Copyright Edward L. Wright, all rights reserved.
// define global variables and functions
var i=0;  // index
var n=1000; // number of points in integrals
var nda = 1;  // number of digits in angular size distance
var H0 = 69.6;  // Hubble constant
var WM = 0.286; // Omega(matter)
var WV = 0.714; // Omega(vacuum) or lambda
var WR = 0; // Omega(radiation)
var WK = 0; // Omega curvaturve = 1-Omega(total)
var z = 3.0;  // redshift of the object
var h = 0.696 // H0/100
var c = 299792.458; // velocity of light in km/sec
var Tyr = 977.8; // coefficent for converting 1/H into Gyr
var DTT = 0.5;  // time from z to now in units of 1/H0
  var DTT_Gyr = 0.0;  // value of DTT in Gyr
var age = 0.5;  // age of Universe in units of 1/H0
  var age_Gyr = 0.0;  // value of age in Gyr
var zage = 0.1; // age of Universe at redshift z in units of 1/H0
  var zage_Gyr = 0.0; // value of zage in Gyr
var DCMR = 0.0; // comoving radial distance in units of c/H0
  var DCMR_Mpc = 0.0;
  var DCMR_Gyr = 0.0;
var DA = 0.0; // angular size distance
  var DA_Mpc = 0.0;
  var DA_Gyr = 0.0;
  var kpc_DA = 0.0;
var DL = 0.0; // luminosity distance
  var DL_Mpc = 0.0;
  var DL_Gyr = 0.0; // DL in units of billions of light years
var V_Gpc = 0.0;
var a = 1.0;  // 1/(1+z), the scale factor of the Universe
var az = 0.5; // 1/(1+z(object))

function computeEnergy (fluence, dl, bandwidth, z)
{
  var e1 = fluence * Math.pow(10,-26) * 0.001;
  var e2 = Math.pow((dl * 3.08567758 * Math.pow(10,25)),2);
  var e3 = bandwidth * (1 + z);
  var e4 = Math.pow(10,32);

  var energy = (e1 * e2 * e3) / e4;
  return energy;
}

// entry point for the input form to pass values back to this script
function updateDerived (ident)
{
  console.log('1')

  var tH0 = parseFloat(document.getElementById('tH0').value);
  var tWM = parseFloat(document.getElementById('tWM').value);
  var tWV = parseFloat(document.getElementById('tWV').value);

  // first compute actual error values
  var tz = parseFloat(document.getElementById(ident + "_redshift").value);
  var fluence = parseFloat(document.getElementById(ident + "_fluence").value);
  var bandwidth = parseFloat(document.getElementById(ident + "_bandwidth").value) * Math.pow(10,6);

  H0 = tH0;
  h = H0/100;
  WM = tWM;
  WV = tWV;
  z = tz;
  WR = 4.165E-5/(h*h);  // includes 3 massless neutrino species, T0 = 2.72528
  WK = 1-WM-WR-WV;

  compute();

  dist_comoving = DCMR_Mpc / 1000.0;
  dist_luminosity = DL_Mpc / 1000.0;
  energy = computeEnergy (fluence, dist_luminosity, bandwidth, z);

  document.getElementById(ident + "_energy").innerHTML = energy.toFixed(2);
  document.getElementById(ident + "_dist_comoving").innerHTML = dist_comoving.toFixed(2);
  document.getElementById(ident + "_dist_luminosity").innerHTML = dist_luminosity.toFixed(2);
}


// entry point for the input form to pass values back to this script
function updateDerivedWithErrors (ident)
{
  var tH0 = parseFloat(document.getElementById('tH0').value);
  var tWM = parseFloat(document.getElementById('tWM').value);
  var tWV = parseFloat(document.getElementById('tWV').value);

  // first compute actual error values
  var tz = parseFloat(document.getElementById(ident + "_redshift").value);
  var fluence = parseFloat(document.getElementById(ident + "_fluence").value);
  var bandwidth = parseFloat(document.getElementById(ident + "_bandwidth").value) * Math.pow(10,6);

  var tzs = [];
  var keys = [];
  keys.push("actual")
  tzs.push(tz);

  if (document.getElementById(ident + "_redshift_error_upper").value != "")
  {
    keys.push("upper")
    tzs.push (tz + parseFloat(document.getElementById(ident + "_redshift_error_upper").value));
  }
  if (document.getElementById(ident + "_redshift_error_lower").value != "")
  {
    keys.push("lower");
    tzs.push(tz + parseFloat(document.getElementById(ident + "_redshift_error_lower").value));
  }

  var dist_comovings = [];
  var dist_luminosities = [];
  var energies = [];

  for (k=0; k<keys.length; k++)
  {
    H0 = tH0;
    h = H0/100;
    WM = tWM;
    WV = tWV;
    z = tzs[k];
    WR = 4.165E-5/(h*h);  // includes 3 massless neutrino species, T0 = 2.72528
    WK = 1-WM-WR-WV;

    compute();

    dist_comovings.push(DCMR_Mpc / 1000.0);
    dist_luminosities.push(DL_Mpc / 1000.0);
    energies.push(computeEnergy (fluence, dist_luminosities[k], bandwidth, z));
  }

  // now we have the computations we need, return the errors to their delta units

  // compute actual energy value first
  var energy = energies[0];
  var dist_comoving = dist_comovings[0];
  var dist_luminosity = dist_luminosities[0];

  for (k=0; k<keys.length; k++)
  {
    if (keys[k] == "actual")
    {
      document.getElementById(ident + "_energy").innerHTML = energy.toFixed(2);
      document.getElementById(ident + "_dist_comoving").innerHTML = dist_comoving.toFixed(2);
      document.getElementById(ident + "_dist_luminosity").innerHTML = dist_luminosity.toFixed(2);
    }
    else if (keys[k] == "upper")
    {
      var energy_upper = energies[k] - energy;
      document.getElementById(ident + "_energy_error_upper").innerHTML = energy_upper.toFixed(2);
      var dist_comoving_error_upper = dist_comovings[k] - dist_comoving;
      document.getElementById(ident + "_dist_comoving_error_upper").innerHTML = "+"+dist_comoving_error_upper.toFixed(2);
      var dist_luminosity_error_upper = dist_luminosities[k] - dist_luminosity;
      document.getElementById(ident + "_dist_luminosity_error_upper").innerHTML = "+"+dist_luminosity_error_upper.toFixed(2);
    }
    else if (keys[k] == "lower")
    {
      var energy_lower = energies[k] - energy;
      document.getElementById(ident + "_energy_error_lower").innerHTML = energy_lower.toFixed(2);
      var dist_comoving_error_lower = dist_comovings[k] - dist_comoving;
      document.getElementById(ident + "_dist_comoving_error_lower").innerHTML = "-"+dist_comoving_error_lower.toFixed(2);
      var dist_luminosity_error_lower = dist_luminosities[k] - dist_luminosity;
      document.getElementById(ident + "_dist_luminosity_error_lower").innerHTML = "-"+dist_luminosity_error_lower.toFixed(2);
    }
  }
}

function stround(x,m) {
// rounds to m digits and makes a string
  var tenn = 1;
  var i = 0;
  for (i=0; i != m; i++) {
    tenn = tenn*10;
  }
  var y = Math.round(Math.abs(x)*tenn);
  var str = " "+y;
  while (m > str.length-2) {
    str = " 0" + str.substring(1,str.length);
  }
  str = str.substring(0,str.length-m)+"."+
        str.substring(str.length-m,str.length);
  if (x < 0) str = " -"+str.substring(1,str.length);
  return str;
}

// tangential comoving distance
function DCMT() {
  var ratio = 1.00;
  var x;
  var y;
  x = Math.sqrt(Math.abs(WK))*DCMR;
  // document.writeln("DCMR = " + DCMR + "<BR>");
  // document.writeln("x = " + x + "<BR>");
  if (x > 0.1) {
    ratio =  (WK > 0) ? 0.5*(Math.exp(x)-Math.exp(-x))/x : Math.sin(x)/x;
    // document.writeln("ratio = " + ratio + "<BR>");
    y = ratio*DCMR;
    return y;
  };
  y = x*x;
// statement below fixed 13-Aug-03 to correct sign error in expansion
  if (WK < 0) y = -y;
  ratio = 1 + y/6 + y*y/120;
  // document.writeln("ratio = " + ratio + "<BR>");
  y= ratio*DCMR;
  return y;
}

// comoving volume computation
function VCM() {
  var ratio = 1.00;
  var x;
  var y;
  x = Math.sqrt(Math.abs(WK))*DCMR;
  if (x > 0.1) {
    ratio =  (WK > 0) ? (0.125*(Math.exp(2*x)-Math.exp(-2*x))-x/2)/(x*x*x/3) :
    (x/2 - Math.sin(2*x)/4)/(x*x*x/3) ;
    y = ratio*DCMR*DCMR*DCMR/3;
    return y;
  };
  y = x*x;
// statement below fixed 13-Aug-03 to correct sign error in expansion
  if (WK < 0) y = -y;
  ratio = 1 + y/5 + (2/105)*y*y;
  y= ratio*DCMR*DCMR*DCMR/3;
  return y;
}

// calculate the actual results
function compute()
{
  h = H0/100;
  WR = 4.165E-5/(h*h);  // includes 3 massless neutrino species, T0 = 2.72528
  WK = 1-WM-WR-WV;
  az = 1.0/(1+1.0*z);
  age = 0;
  for (i = 0; i != n; i++) {
    a = az*(i+0.5)/n;
    adot = Math.sqrt(WK+(WM/a)+(WR/(a*a))+(WV*a*a));
    age = age + 1/adot;
  };
  zage = az*age/n;
// correction for annihilations of particles not present now like e+/e-
// added 13-Aug-03 based on T_vs_t.f
  var lpz = Math.log((1+1.0*z))/Math.log(10.0);
  var dzage = 0;
  if (lpz >  7.500) dzage = 0.002 * (lpz -  7.500);
  if (lpz >  8.000) dzage = 0.014 * (lpz -  8.000) +  0.001;
  if (lpz >  8.500) dzage = 0.040 * (lpz -  8.500) +  0.008;
  if (lpz >  9.000) dzage = 0.020 * (lpz -  9.000) +  0.028;
  if (lpz >  9.500) dzage = 0.019 * (lpz -  9.500) +  0.039;
  if (lpz > 10.000) dzage = 0.048;
  if (lpz > 10.775) dzage = 0.035 * (lpz - 10.775) +  0.048;
  if (lpz > 11.851) dzage = 0.069 * (lpz - 11.851) +  0.086;
  if (lpz > 12.258) dzage = 0.461 * (lpz - 12.258) +  0.114;
  if (lpz > 12.382) dzage = 0.024 * (lpz - 12.382) +  0.171;
  if (lpz > 13.055) dzage = 0.013 * (lpz - 13.055) +  0.188;
  if (lpz > 14.081) dzage = 0.013 * (lpz - 14.081) +  0.201;
  if (lpz > 15.107) dzage = 0.214;
  zage = zage*Math.pow(10.0,dzage);
//
  zage_Gyr = (Tyr/H0)*zage;
  DTT = 0.0;
  DCMR = 0.0;
// do integral over a=1/(1+z) from az to 1 in n steps, midpoint rule
  for (i = 0; i != n; i++) {
    a = az+(1-az)*(i+0.5)/n;
    adot = Math.sqrt(WK+(WM/a)+(WR/(a*a))+(WV*a*a));
    DTT = DTT + 1/adot;
    DCMR = DCMR + 1/(a*adot);
  };
  DTT = (1-az)*DTT/n;
  DCMR = (1-az)*DCMR/n;
  age = DTT+zage;
  age_Gyr = age*(Tyr/H0);
  DTT_Gyr = (Tyr/H0)*DTT;
  DCMR_Gyr = (Tyr/H0)*DCMR;
  DCMR_Mpc = (c/H0)*DCMR;
  DA = az*DCMT();
  DA_Mpc = (c/H0)*DA;
  kpc_DA = DA_Mpc/206.264806;
  DA_Gyr = (Tyr/H0)*DA;
  DL = DA/(az*az);
  DL_Mpc = (c/H0)*DL;
  DL_Gyr = (Tyr/H0)*DL;
  V_Gpc = 4*Math.PI*Math.pow(0.001*c/H0,3)*VCM();

  return;
}
