defmodule PhoenixApiWeb.PhotoControllerTest do
  use PhoenixApiWeb.ConnCase

  alias PhoenixApi.RateLimiter
  alias PhoenixApi.Repo
  alias PhoenixApi.Accounts.User
  alias PhoenixApi.Media.Photo

  setup do
    RateLimiter.reset!()

    user =
      %User{}
      |> User.changeset(%{api_token: "valid_test_token_123"})
      |> Repo.insert!()

    other_user =
      %User{}
      |> User.changeset(%{api_token: "other_user_token_456"})
      |> Repo.insert!()

    photo1 =
      %Photo{}
      |> Photo.changeset(%{
        photo_url: "https://example.com/photo1.jpg",
        camera: "Canon EOS R5",
        lens: "RF 24-70mm f/2.8",
        settings: "Manual mode",
        description: "Beautiful sunset",
        location: "Beach",
        focal_length: "50mm",
        aperture: "f/2.8",
        shutter_speed: "1/200",
        iso: 100,
        user_id: user.id
      })
      |> Repo.insert!()

    photo2 =
      %Photo{}
      |> Photo.changeset(%{
        photo_url: "https://example.com/photo2.jpg",
        camera: "Sony A7III",
        lens: "FE 85mm f/1.8",
        description: "Portrait shot",
        location: "Studio",
        user_id: user.id
      })
      |> Repo.insert!()

    _other_photo =
      %Photo{}
      |> Photo.changeset(%{
        photo_url: "https://example.com/photo3.jpg",
        camera: "Nikon Z6",
        user_id: other_user.id
      })
      |> Repo.insert!()

    {:ok, user: user, other_user: other_user, photo1: photo1, photo2: photo2}
  end

  describe "GET /api/photos" do
    test "returns photos for authenticated user", %{conn: conn, photo1: photo1, photo2: photo2} do
      conn =
        conn
        |> put_req_header("access-token", "valid_test_token_123")
        |> get("/api/photos")

      assert json_response(conn, 200) == %{
               "photos" => [
                 %{
                   "id" => photo1.id,
                   "photo_url" => photo1.photo_url,
                   "camera" => photo1.camera,
                   "description" => photo1.description,
                   "location" => photo1.location,
                   "taken_at" => nil
                 },
                 %{
                   "id" => photo2.id,
                   "photo_url" => photo2.photo_url,
                   "camera" => photo2.camera,
                   "description" => photo2.description,
                   "location" => photo2.location,
                   "taken_at" => nil
                 }
               ]
             }
    end

    test "returns import payload fields", %{conn: conn, photo1: photo1} do
      conn =
        conn
        |> put_req_header("access-token", "valid_test_token_123")
        |> get("/api/photos")

      response = json_response(conn, 200)
      photos = response["photos"]

      assert length(photos) > 0

      first_photo = List.first(photos)
      assert Map.keys(first_photo) |> Enum.sort() ==
               ["camera", "description", "id", "location", "photo_url", "taken_at"]

      assert first_photo["id"] == photo1.id
      assert first_photo["photo_url"] == photo1.photo_url
    end

    test "returns empty array when user has no photos", %{conn: conn} do
      _new_user =
        %User{}
        |> User.changeset(%{api_token: "new_user_token"})
        |> Repo.insert!()

      conn =
        conn
        |> put_req_header("access-token", "new_user_token")
        |> get("/api/photos")

      assert json_response(conn, 200) == %{"photos" => []}
    end

    test "returns 401 when access-token header is missing", %{conn: conn} do
      conn = get(conn, "/api/photos")

      assert json_response(conn, 401) == %{
               "errors" => %{"detail" => "Unauthorized"}
             }
    end

    test "returns 401 when access-token is invalid", %{conn: conn} do
      conn =
        conn
        |> put_req_header("access-token", "invalid_token")
        |> get("/api/photos")

      assert json_response(conn, 401) == %{
               "errors" => %{"detail" => "Unauthorized"}
             }
    end

    test "different users see only their own photos", %{conn: conn} do
      conn =
        conn
        |> put_req_header("access-token", "other_user_token_456")
        |> get("/api/photos")

      response = json_response(conn, 200)
      assert length(response["photos"]) == 1
      assert Enum.at(response["photos"], 0)["photo_url"] == "https://example.com/photo3.jpg"
    end

    test "returns 429 when per-user rate limit is exceeded", %{conn: conn} do
      previous = Application.get_env(:phoenix_api, :import_rate_limit)

      Application.put_env(:phoenix_api, :import_rate_limit,
        user_limit: 2,
        user_window_seconds: 600,
        global_limit: 1000,
        global_window_seconds: 3600
      )

      on_exit(fn ->
        Application.put_env(:phoenix_api, :import_rate_limit, previous)
        RateLimiter.reset!()
      end)

      RateLimiter.reset!()

      conn1 =
        conn
        |> put_req_header("access-token", "valid_test_token_123")
        |> get("/api/photos")

      assert conn1.status == 200

      conn2 =
        build_conn()
        |> put_req_header("access-token", "valid_test_token_123")
        |> get("/api/photos")

      assert conn2.status == 200

      conn3 =
        build_conn()
        |> put_req_header("access-token", "valid_test_token_123")
        |> get("/api/photos")

      assert conn3.status == 429
      assert json_response(conn3, 429) == %{"errors" => %{"detail" => "Too Many Requests"}}
    end
  end
end
