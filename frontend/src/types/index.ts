export interface IPostIndexData {
  post_code: string;
  region: string;
  district_old: string;
  district_new: string;
  city: string;
  post_office: string;
}

export interface IPaginationData {
  total: string;
  per_page: string;
  current_page: string;
  last_page: string;
  from: string;
  to: string;
}
