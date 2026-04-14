defmodule PhoenixApiWeb.Plugs.RateLimitImport do
  import Plug.Conn
  import Phoenix.Controller

  alias PhoenixApi.RateLimiter

  def init(opts), do: opts

  def call(conn, _opts) do
    limits = Application.fetch_env!(:phoenix_api, :import_rate_limit)
    access_token = get_req_header(conn, "access-token") |> List.first() |> normalize_token()

    with :ok <- RateLimiter.allow?({:global, :import}, limits[:global_limit], limits[:global_window_seconds]),
         :ok <- RateLimiter.allow?({:user, access_token}, limits[:user_limit], limits[:user_window_seconds]) do
      conn
    else
      {:error, retry_after} ->
        conn
        |> put_resp_header("retry-after", Integer.to_string(retry_after))
        |> put_status(:too_many_requests)
        |> put_view(json: PhoenixApiWeb.ErrorJSON)
        |> render(:"429")
        |> halt()
    end
  end

  defp normalize_token(nil), do: "anonymous"
  defp normalize_token(""), do: "anonymous"
  defp normalize_token(token), do: token
end
