// services/seatService.js
// ✅ ALL calls go through Laravel — no company IP, username, or password visible in browser

export const SEAT_STATUS = {
    AVAILABLE: "Available",
    EMPTY: "Empty",
    RESERVED: "Reserved",
    HOLD: "Hold",
};

class SeatService {
    constructor() {
        this.abortController = null;
    }

    _getCsrfToken() {
        return (
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || ""
        );
    }

    // ─────────────────────────────────────────────────────────────
    // FETCH SEATS → GET /api/seats  (SeatPlaneController@index)
    // ─────────────────────────────────────────────────────────────
    async fetchSeats(params) {
        const { company, from, to, date, time, serviceTypeId, scheduleId, operator_id } = params;

        if (!company)    throw new Error("Company is required");
        if (!scheduleId) throw new Error("Schedule ID is required");
        if (!operator_id) throw new Error("Operator ID is required");

        this.abortController = new AbortController();
        const timeout = setTimeout(() => this.abortController.abort(), 15000);

        try {
            const query = new URLSearchParams({
                company, from, to, date, time, serviceTypeId, scheduleId, operator_id,
            }).toString();

            // ✅ Clean URL — no IP, no username, no password
            const response = await fetch(`/api/seats?${query}`, {
                signal: this.abortController.signal,
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this._getCsrfToken(),
                },
            });

            clearTimeout(timeout);

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();

            if (!data.success) throw new Error(data.message || "Failed to fetch seats");

            return {
                success: true,
                seats:          data.seats         || [],
                totalSeats:     data.totalSeats     || 0,
                availableSeats: data.availableSeats || 0,
                discount:       data.discount       || null,
                baseFare:       data.baseFare || data.total_fare || 0,
                seat4Fare:      data.seat_4_fare  || 0,
                seat20Fare:     data.seat_20_fare || 0,
            };
        } catch (error) {
            clearTimeout(timeout);
            console.error("Seat fetch error:", error);
            throw error;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // HOLD SEAT → POST /api/seats/hold  (SeatPlaneController@holdSeats)
    // ─────────────────────────────────────────────────────────────
    async holdSeat(company, scheduleId, seatId) {
        if (!company) throw new Error("Company is required");

        const controller = new AbortController();
        const timeout    = setTimeout(() => controller.abort(), 30000);

        try {
            // ✅ POST to Laravel — credentials added server-side only
            const response = await fetch("/api/seats/hold", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept:         "application/json",
                    "X-CSRF-TOKEN": this._getCsrfToken(),
                },
                body: JSON.stringify({ company, scheduleId, seatId }),
                signal: controller.signal,
            });

            clearTimeout(timeout);

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const result = await response.json();

            if (!result.success) throw new Error(result.message || "Seat hold failed");

            return {
                success: true,
                message: "Seat held successfully",
                data:    result.api_result,
            };
        } catch (error) {
            clearTimeout(timeout);
            throw error;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // UNHOLD SEAT → POST /api/seats/unhold  (SeatPlaneController@unholdSeats)
    // ─────────────────────────────────────────────────────────────
    async unholdSeat(company, scheduleId, seatId) {
        if (!company) throw new Error("Company is required");

        const controller = new AbortController();
        const timeout    = setTimeout(() => controller.abort(), 30000);

        try {
            // ✅ POST to Laravel — credentials added server-side only
            const response = await fetch("/api/seats/unhold", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept:         "application/json",
                    "X-CSRF-TOKEN": this._getCsrfToken(),
                },
                body: JSON.stringify({ company, scheduleId, seatId }),
                signal: controller.signal,
            });

            clearTimeout(timeout);

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const result = await response.json();

            if (!result.success) throw new Error(result.message || "Seat unhold failed");

            return {
                success: true,
                message: "Seat released successfully",
                data:    result.api_result,
            };
        } catch (error) {
            clearTimeout(timeout);
            throw error;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // GET TERMINALS → GET /api/terminals  (SeatPlaneController@getSourceTerminals)
    // ─────────────────────────────────────────────────────────────
    async getSourceTerminals(params) {
        const { company, scheduleId, fromId, departureTime, serviceTypeId } = params;

        if (!company) throw new Error("Company is required");

        const controller = new AbortController();
        const timeout    = setTimeout(() => controller.abort(), 30000);

        try {
            const query = new URLSearchParams({
                company, scheduleId, fromId, departureTime, serviceTypeId,
            }).toString();

            const response = await fetch(`/api/terminals?${query}`, {
                headers: {
                    Accept:         "application/json",
                    "X-CSRF-TOKEN": this._getCsrfToken(),
                },
                signal: controller.signal,
            });

            clearTimeout(timeout);

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();

            if (!data.success) throw new Error(data.message || "Failed to fetch terminals");

            return {
                success:        true,
                terminals:      data.terminals      || [],
                totalTerminals: data.totalTerminals || 0,
            };
        } catch (error) {
            clearTimeout(timeout);
            throw error;
        }
    }

    cancel() {
        if (this.abortController) {
            this.abortController.abort();
        }
    }
}

// ─────────────────────────────────────────────────────────────
// Singleton — same exports as before, SeatGrid.vue unchanged
// ─────────────────────────────────────────────────────────────
export const seatService = new SeatService();

export const fetchSeats = (params) =>
    seatService.fetchSeats(params);

export const holdSeat = (company, scheduleId, seatId) =>
    seatService.holdSeat(company, scheduleId, seatId);

export const unholdSeat = (company, scheduleId, seatId) =>
    seatService.unholdSeat(company, scheduleId, seatId);

export const getSourceTerminals = (params) =>
    seatService.getSourceTerminals(params);
