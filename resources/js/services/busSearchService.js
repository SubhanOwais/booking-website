// services/busSearchService.js
// ✅ GET + SSE — results stream in per-company as they arrive
// Company 1 shows immediately, Company 2 appends when ready

/**
 * searchBuses — fires GET /api/search and listens to SSE stream
 *
 * @param {number}   fromId          - City ID (from)
 * @param {number}   toId            - City ID (to)
 * @param {string}   date            - Travel date (YYYY-MM-DD)
 * @param {Function} onCompanyResult - Called immediately when each company returns
 *                                     receives: { operator_id, company, trips, count, discount }
 * @param {Function} onFetching      - Called when a company fetch starts (for loading indicator)
 *                                     receives: { operator_id, company, index }
 * @param {Function} onDone          - Called once ALL companies are done
 *                                     receives: { total_trips, message }
 * @param {Function} onError         - Called if a company fails
 *                                     receives: { operator_id, company, reason }
 *
 * @returns {Function} cancel — call this to abort the stream early (e.g. on page leave)
 */
export const searchBuses = (
    fromId,
    toId,
    date,
    { onCompanyResult, onFetching, onDone, onError } = {}
) => {
    // Build query string — GET request, params go in URL
    const params = new URLSearchParams({ fromId, toId, date });
    const url    = `/api/search?${params.toString()}`;

    // EventSource handles SSE + CORS preflight automatically
    // Browser fires OPTIONS preflight first, then GET — this is what you wanted
    const eventSource = new EventSource(url, { withCredentials: false });

    // ─────────────────────────────────────────────────────────────────────
    // Event: "fetching" — a company request just started
    // Use this to show a "Loading Company X..." skeleton/spinner
    // ─────────────────────────────────────────────────────────────────────
    eventSource.addEventListener("fetching", (e) => {
        const data = JSON.parse(e.data);
        // e.g. { operator_id: "1", company: "Royal Express", index: 1 }
        if (onFetching) onFetching(data);
    });

    // ─────────────────────────────────────────────────────────────────────
    // Event: "company_results" — THIS IS THE KEY EVENT
    // Fires as soon as one company's data is ready
    // Company 1 arrives → rendered immediately
    // Company 2 arrives 3 seconds later → appended to results
    // ─────────────────────────────────────────────────────────────────────
    eventSource.addEventListener("company_results", (e) => {
        const data = JSON.parse(e.data);
        // data = { operator_id, company, index, trips: [...], count, discount }
        if (onCompanyResult) onCompanyResult(data);
    });

    // ─────────────────────────────────────────────────────────────────────
    // Event: "company_failed" — a company API was unreachable
    // ─────────────────────────────────────────────────────────────────────
    eventSource.addEventListener("company_failed", (e) => {
        const data = JSON.parse(e.data);
        if (onError) onError(data);
    });

    // Event: "company_empty" — company returned no trips for this route
    eventSource.addEventListener("company_empty", (e) => {
        const data = JSON.parse(e.data);
        if (onError) onError({ ...data, reason: "No trips found" });
    });

    // ─────────────────────────────────────────────────────────────────────
    // Event: "done" — all companies finished, stream will close
    // ─────────────────────────────────────────────────────────────────────
    eventSource.addEventListener("done", (e) => {
        const data = JSON.parse(e.data);
        if (onDone) onDone(data);
        eventSource.close(); // Clean up the connection
    });

    // Event: "error" — server-level error
    eventSource.addEventListener("error", (e) => {
        console.error("SSE connection error:", e);
        if (onError) onError({ reason: "Stream connection failed" });
        eventSource.close();
    });

    // Return a cancel function — call on component unmount / navigation away
    return () => eventSource.close();
};
