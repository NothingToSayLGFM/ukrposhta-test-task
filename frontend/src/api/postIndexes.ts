import { api } from "@/api/client";
import type { IPostIndexData, IPaginationData } from "@/types";

export interface ListParams {
  page?: number;
  q?: string;
}

export async function searchPostIndexes(params: ListParams) {
  const { data } = await api.get<{
    data: IPostIndexData[];
    meta: IPaginationData;
  }>("/post-indexes", {
    params,
  });

  return data;
}

export async function deletePostIndex(id: string) {
  return await api.delete("/post-indexes", {
    data: {
      post_codes: [id],
    },
  });
}

export async function createPostIndex(data: IPostIndexData) {
  return await api.post("/post-indexes", [data]);
}

export async function getPostIndexById(id: string) {
  return await api.get<IPostIndexData>("/post-indexes", {
    params: {
      post_code: id,
    },
  });
}
