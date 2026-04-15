// =============================================================================
// store/SeatPlaneDesign.js
// -----------------------------------------------------------------------------
// Centralised seat-plane layout definitions for every bus service type.
// Each entry maps a `serviceTypeId` (from the API / URL params) to a human-
// readable name and a sectioned row/column grid.
//
// Layout convention:
//   • Each row is an array of seat-number strings (or `null` for empty space).
//   • null cells render as invisible spacers so the aisle gap is preserved.
//   • Seat numbers must match the Seat_No / SeatNo values returned by the API.
// =============================================================================

export const busServiceConfigs = {
    // ─────────────────────────────────────────────────────────────────────────
    // 13 — Executive  (5-column layout: 2 | aisle | 2, last row full bench)
    // ─────────────────────────────────────────────────────────────────────────
    Executive: {
        serviceTypeId: "13",
        name: "Executive",
        sections: [
            {
                name: "Executive",
                type: "executive",
                rows: [
                    { cols: ["4",  "3",  null, "2",  "1"]  },
                    { cols: ["8",  "7",  null, "6",  "5"]  },
                    { cols: ["12", "11", null, "10", "9"]  },
                    { cols: ["16", "15", null, "14", "13"] },
                    { cols: ["20", "19", null, "18", "17"] },
                    { cols: ["24", "23", null, "22", "21"] },
                    { cols: ["28", "27", null, "26", "25"] },
                    { cols: ["32", "31", null, "30", "29"] },
                    { cols: ["36", "35", null, "34", "33"] },
                    { cols: ["40", "39", null, "38", "37"] },
                    { cols: ["44", "43", null, "42", "41"] },
                    // Last row — full 5-seat bench
                    { cols: ["49", "48", "47", "46", "45"] },
                ],
            },
        ],
    },

    // ─────────────────────────────────────────────────────────────────────────
    // 14 — Executive Plus  (first 3 rows: 1 | aisle×2 | 2, rest: 2 | aisle | 2)
    // ─────────────────────────────────────────────────────────────────────────
    Executive_Plus: {
        serviceTypeId: "14",
        name: "Executive Plus",
        sections: [
            {
                name: "Executive Plus",
                type: "executive plus",
                rows: [
                    // First 3 rows — 1 seat on the left, 2 on the right
                    { cols: ["3",  null, null, "2",  "1"]  },
                    { cols: ["6",  null, null, "5",  "4"]  },
                    { cols: ["9",  null, null, "8",  "7"]  },
                    // Remaining rows — 2 seats on each side
                    { cols: ["13", "12", null, "11", "10"] },
                    { cols: ["17", "16", null, "15", "14"] },
                    { cols: ["21", "20", null, "19", "18"] },
                    { cols: ["25", "24", null, "23", "22"] },
                    { cols: ["29", "28", null, "27", "26"] },
                    { cols: ["33", "32", null, "31", "30"] },
                    { cols: ["37", "36", null, "35", "34"] },
                    // Last row — full 5-seat bench
                    { cols: ["42", "41", "40", "39", "38"] },
                ],
            },
        ],
    },

    // ─────────────────────────────────────────────────────────────────────────
    // 15 — AC Sleeper  (berth pairs: upper row above lower row, 1 | aisle×2 | 2)
    // Each pair of consecutive rows represents upper/lower berths of one bay.
    // ─────────────────────────────────────────────────────────────────────────
    ac_sleeper: {
        serviceTypeId: "15",
        name: "AC Sleeper",
        sections: [
            {
                name: "AC Sleeper",
                type: "AC Sleeper",
                rows: [
                    // Bay 1 — upper / lower
                    { cols: ["6",  null, null, "4",  "3"]  },
                    { cols: ["5",  null, null, "2",  "1"]  },
                    // Bay 2
                    { cols: ["12", null, null, "10", "9"]  },
                    { cols: ["11", null, null, "8",  "7"]  },
                    // Bay 3
                    { cols: ["18", null, null, "16", "15"] },
                    { cols: ["17", null, null, "14", "13"] },
                    // Bay 4
                    { cols: ["24", null, null, "22", "21"] },
                    { cols: ["23", null, null, "20", "19"] },
                    // Bay 5
                    { cols: ["30", null, null, "28", "27"] },
                    { cols: ["29", null, null, "26", "25"] },
                    // Last row — mixed bench
                    { cols: ["34", null, "33", "32", "31"] },
                ],
            },
        ],
    },

    // ─────────────────────────────────────────────────────────────────────────
    // 16 — Executive Plus 41  (identical layout to Executive_Plus but 41 seats)
    // ─────────────────────────────────────────────────────────────────────────
    Executive_Plus_41: {
        serviceTypeId: "16",
        name: "Executive Plus 41",
        sections: [
            {
                name: "Executive Plus 41",
                type: "executive",
                rows: [
                    { cols: ["3",  null, null, "2",  "1"]  },
                    { cols: ["6",  null, null, "5",  "4"]  },
                    { cols: ["9",  null, null, "8",  "7"]  },
                    { cols: ["13", "12", null, "11", "10"] },
                    { cols: ["17", "16", null, "15", "14"] },
                    { cols: ["21", "20", null, "19", "18"] },
                    { cols: ["25", "24", null, "23", "22"] },
                    { cols: ["29", "28", null, "27", "26"] },
                    { cols: ["33", "32", null, "31", "30"] },
                    { cols: ["37", "36", null, "35", "34"] },
                    { cols: ["41", "40", null, "39", "38"] },
                ],
            },
        ],
    },

    // ─────────────────────────────────────────────────────────────────────────
    // 17 — Premium Business  (1 | aisle×2 | 2 — pure business class, 30 seats)
    // ─────────────────────────────────────────────────────────────────────────
    premium_business: {
        serviceTypeId: "17",
        name: "Premium Business",
        sections: [
            {
                name: "Business Class",
                type: "business",
                rows: [
                    { cols: ["3",  null, null, "2",  "1"]  },
                    { cols: ["6",  null, null, "5",  "4"]  },
                    { cols: ["9",  null, null, "8",  "7"]  },
                    { cols: ["12", null, null, "11", "10"] },
                    { cols: ["15", null, null, "14", "13"] },
                    { cols: ["18", null, null, "17", "16"] },
                    { cols: ["21", null, null, "20", "19"] },
                    { cols: ["24", null, null, "23", "22"] },
                    { cols: ["27", null, null, "26", "25"] },
                    { cols: ["30", null, null, "29", "28"] },
                ],
            },
        ],
    },

    // ─────────────────────────────────────────────────────────────────────────
    // 18 — Premium Business 12×28  (12 business seats + 28 executive seats)
    // Two distinct sections with different aisle widths.
    // ─────────────────────────────────────────────────────────────────────────
    premium_business_12x28: {
        serviceTypeId: "18",
        name: "Premium Business 12X28",
        sections: [
            // ── Front section — Business Class (12 seats) ────────────────────
            {
                name: "Business Class",
                type: "business",
                rows: [
                    { cols: ["3",  null, null, "2",  "1"]  },
                    { cols: ["6",  null, null, "5",  "4"]  },
                    { cols: ["9",  null, null, "8",  "7"]  },
                    { cols: ["12", null, null, "11", "10"] },
                ],
            },
            // ── Rear section — Executive Class (28 seats) ────────────────────
            {
                name: "Executive Class",
                type: "executive",
                rows: [
                    { cols: ["16", "15", null, "14", "13"] },
                    { cols: ["20", "19", null, "18", "17"] },
                    { cols: ["24", "23", null, "22", "21"] },
                    { cols: ["28", "27", null, "26", "25"] },
                    { cols: ["32", "31", null, "30", "29"] },
                    { cols: ["36", "35", null, "34", "33"] },
                    { cols: ["40", "39", null, "38", "37"] },
                ],
            },
        ],
    },

    // ─────────────────────────────────────────────────────────────────────────
    // 19 — Premium Business 9×32  (9 business seats + 32 executive seats)
    // ─────────────────────────────────────────────────────────────────────────
    premium_business_9x32: {
        serviceTypeId: "19",
        name: "Premium Business 9X32",
        sections: [
            // ── Front section — Business Class (9 seats) ─────────────────────
            {
                name: "Business Class",
                type: "business",
                rows: [
                    { cols: ["3", null, null, "2", "1"] },
                    { cols: ["6", null, null, "5", "4"] },
                    { cols: ["9", null, null, "8", "7"] },
                ],
            },
            // ── Rear section — Executive Class (32 seats) ────────────────────
            {
                name: "Executive Class",
                type: "executive",
                rows: [
                    { cols: ["13", "12", null, "11", "10"] },
                    { cols: ["17", "16", null, "15", "14"] },
                    { cols: ["21", "20", null, "19", "18"] },
                    { cols: ["25", "24", null, "23", "22"] },
                    { cols: ["29", "28", null, "27", "26"] },
                    { cols: ["33", "32", null, "31", "30"] },
                    { cols: ["37", "36", null, "35", "34"] },
                    { cols: ["41", "40", null, "39", "38"] },
                ],
            },
        ],
    },

    // ─────────────────────────────────────────────────────────────────────────
    // 20 — Executive Class  (2 | aisle | 2 — 48 seats, no premium front rows)
    // ─────────────────────────────────────────────────────────────────────────
    Executive_Class: {
        serviceTypeId: "20",
        name: "Executive Class",
        sections: [
            {
                name: "Executive Class",
                type: "executive class",
                rows: [
                    { cols: ["4",  "3",  null, "2",  "1"]  },
                    { cols: ["8",  "7",  null, "6",  "5"]  },
                    { cols: ["12", "11", null, "10", "9"]  },
                    { cols: ["16", "15", null, "14", "13"] },
                    { cols: ["20", "19", null, "18", "17"] },
                    { cols: ["24", "23", null, "22", "21"] },
                    { cols: ["28", "27", null, "26", "25"] },
                    { cols: ["32", "31", null, "30", "29"] },
                    { cols: ["36", "35", null, "34", "33"] },
                    { cols: ["40", "39", null, "38", "37"] },
                    { cols: ["44", "43", null, "42", "41"] },
                    { cols: ["48", "47", null, "46", "45"] },
                ],
            },
        ],
    },

    // ─────────────────────────────────────────────────────────────────────────
    // 23 — AC Sleeper Royal  (1 | aisle×2 | 2 — 31 sleeper berths, last single)
    // ─────────────────────────────────────────────────────────────────────────
    ac_sleeper_royal: {
        serviceTypeId: "23",
        name: "AC Sleeper Royal",
        sections: [
            {
                name: "AC Sleeper Royal",
                type: "AC Sleeper Royal",
                rows: [
                    { cols: ["3",  null, null, "2",  "1"]  },
                    { cols: ["6",  null, null, "5",  "4"]  },
                    { cols: ["9",  null, null, "8",  "7"]  },
                    { cols: ["12", null, null, "11", "10"] },
                    { cols: ["15", null, null, "14", "13"] },
                    { cols: ["18", null, null, "17", "16"] },
                    { cols: ["21", null, null, "20", "19"] },
                    { cols: ["24", null, null, "23", "22"] },
                    { cols: ["27", null, null, "26", "25"] },
                    { cols: ["30", null, null, "29", "28"] },
                    // Last row — single centre seat
                    { cols: [null, null, "31", null, null] },
                ],
            },
        ],
    },
};

// =============================================================================
// Helper — resolve config by serviceTypeId string
// Returns the matching config or falls back to Executive (id: 13).
// =============================================================================
export function getConfigByServiceTypeId(serviceTypeId) {
    const match = Object.values(busServiceConfigs).find(
        (cfg) => String(cfg.serviceTypeId) === String(serviceTypeId)
    );

    if (!match) {
        console.warn(
            `[SeatPlaneDesign] No config found for serviceTypeId "${serviceTypeId}". ` +
            `Falling back to Executive (id: 13).`
        );
    }

    return match || busServiceConfigs.Executive;
}

// =============================================================================
// Helper — list all available service types (useful for dropdowns / debug)
// =============================================================================
export function getAllServiceTypes() {
    return Object.values(busServiceConfigs).map(({ serviceTypeId, name }) => ({
        serviceTypeId,
        name,
    }));
}
