from __future__ import annotations

from engine.intelligence.location import detect_locations


PLACES = [
    {"id": "pune-id", "slug": "pune", "name": "Pune", "aliases": []},
    {"id": "baner-id", "slug": "baner", "name": "Baner", "aliases": []},
    {"id": "shivaji-id", "slug": "shivajinagar", "name": "Shivajinagar", "aliases": ["Shivaji Nagar"]},
]


def main() -> None:
    hits = detect_locations(
        "Traffic restrictions near Baner, Pune. The story is from Pune Mirror.",
        PLACES,
        title="Traffic restrictions near Baner",
    )
    assert [hit["slug"] for hit in hits] == ["baner", "pune"], hits
    assert hits[0]["headline_match"] is True
    assert hits[0]["confidence"] > hits[1]["confidence"]
    assert hits[0]["decision_source"] == "explicit_place_mention"
    assert detect_locations("Pune Mirror publication only", PLACES) == []
    assert detect_locations("Banerjee reports traffic from another town", PLACES) == []

    marathi = detect_locations("शिवाजीनगर येथे वाहतूक बदल", PLACES)
    assert marathi and marathi[0]["slug"] == "shivajinagar", marathi

    aliases = detect_locations("Changes at Shivaji Nagar junction", PLACES)
    assert aliases and aliases[0]["slug"] == "shivajinagar", aliases
    assert detect_locations("Unspecified place", PLACES) == []
    print("Pune area-mapping text extraction smoke: PASS")


if __name__ == "__main__":
    main()
