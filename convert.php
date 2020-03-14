<?php

$sources = [
  "confirmed" => "https://raw.githubusercontent.com/CSSEGISandData/COVID-19/master/csse_covid_19_data/csse_covid_19_time_series/time_series_19-covid-Confirmed.csv",
  "deaths" => "https://raw.githubusercontent.com/CSSEGISandData/COVID-19/master/csse_covid_19_data/csse_covid_19_time_series/time_series_19-covid-Deaths.csv",
  "recovered" => "https://raw.githubusercontent.com/CSSEGISandData/COVID-19/master/csse_covid_19_data/csse_covid_19_time_series/time_series_19-covid-Recovered.csv",
];

$datas = [];
$datas_parsed = [];

foreach ($sources as $source => $url) {

  $handle = fopen($url, "r");
  $header = false;
  while (($line = fgetcsv($handle)) !== false) {
    if (!$header) {
      $header = $line;
    } else {
      $datas[] = array_combine($header, $line);
    }
  }
  fclose ($handle);

  foreach ($datas as $data) {
    $found_dates = false;
    $data_parsed = [];
    foreach ($data as $key => $value) {
      if (preg_match('@^(?<month>\d+)/(?<day>\d+)/(?<year>\d+)$@', $key, $matches)) {
        if (!$found_dates) {
          $data_parsed['hash'] = sha1(json_encode($data_parsed));
          $found_dates = true;
          if (!isset($datas_parsed[$data_parsed['hash']])) {
            $datas_parsed[$data_parsed['hash']] = $data_parsed;
          }
        }
        $datas_parsed[$data_parsed['hash']]['dates'][sprintf("20%02d-%02d-%02d", $matches['year'], $matches['month'], $matches['day'])][$source] = $value;
      } else {
        $data_parsed[$key] = $value;
      }
    }
  }
}

$datas_sql = [];

foreach ($datas_parsed as $data_parsed) {
  $data_sql_global = [];
  foreach ($data_parsed as $key => $value) {
    if ($key == 'hash') {
      /* Ignore */
    } elseif ($key != 'dates') {
      $data_sql_global[strtr(strtolower($key), [ '/' => '_' ])] = $value ?: null;
    } else {
      foreach ($data_parsed['dates'] as $date => $date_data) {
        $data_sql_local = $data_sql_global;
        $data_sql_local['date'] = $date;
        $data_sql_local += $date_data;
        $datas_sql[] = $data_sql_local;
      }
    }
  }
}

$header = false;
$output = fopen("php://stdout", "w");
foreach ($datas_sql as $data_sql) {
  if (!$header) {
    fputcsv($output, array_keys($data_sql));
    $header = true;
  }
  fputcsv($output, array_values($data_sql));
}
fclose($output);
