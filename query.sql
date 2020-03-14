select
  date,
  confirmed, confirmed - coalesce(lag(confirmed) over(order by date), 0) as confirmed_delta,
  deaths, deaths - coalesce(lag(deaths) over(order by date), 0) as deaths_delta, (case when confirmed > 0 then round(100.00 * deaths/confirmed,2) else 0.00 end) as deaths_pct,
  recovered, recovered - coalesce(lag(recovered) over(order by date), 0) as recovered_delta, (case when confirmed - deaths > 0 then round(100.00 * recovered/(confirmed - deaths),2) else 0.00 end) as recovered_pct
from (
  select date, sum(confirmed) as confirmed, sum(deaths) as deaths, sum(recovered) as recovered from coronavirus where country_region = 'Italy' group by date
) as s
order by
  date
