defmodule PhoenixApi.RateLimiter do
  @moduledoc false
  use GenServer

  @table :phoenix_api_rate_limiter

  def start_link(_opts) do
    GenServer.start_link(__MODULE__, %{}, name: __MODULE__)
  end

  def allow?(key, limit, window_seconds) do
    GenServer.call(__MODULE__, {:allow?, key, limit, window_seconds})
  end

  def reset! do
    GenServer.call(__MODULE__, :reset)
  end

  @impl true
  def init(state) do
    :ets.new(@table, [:named_table, :public, :set, read_concurrency: true])
    {:ok, state}
  end

  @impl true
  def handle_call({:allow?, key, limit, window_seconds}, _from, state) do
    now = System.system_time(:second)
    window_start = now - window_seconds

    timestamps =
      case :ets.lookup(@table, key) do
        [{^key, values}] -> values
        [] -> []
      end
      |> Enum.filter(&(&1 > window_start))

    if length(timestamps) >= limit do
      retry_after = Enum.min(timestamps) + window_seconds - now
      {:reply, {:error, max(retry_after, 1)}, state}
    else
      :ets.insert(@table, {key, [now | timestamps]})
      {:reply, :ok, state}
    end
  end

  @impl true
  def handle_call(:reset, _from, state) do
    :ets.delete_all_objects(@table)
    {:reply, :ok, state}
  end
end
