import request from '@/utils/request';

export async function searchCountries(q: string) {
  return searchLocation('country', q);
}

export async function searchRegions(q: string) {
  return searchLocation('region', q);
}

export async function searchCities(q: string) {
  return searchLocation('city', q);
}

async function searchLocation(location_types: 'region' | 'country' | 'city', q: string) {
  const params: any = { location_types };
  if (q) {
    params.q = q;
  }
  return request.get('/meta-ad-creation/targeting-search', { params });
}
