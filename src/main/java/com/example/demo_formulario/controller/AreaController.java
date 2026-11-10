package com.example.demo_formulario.controller;

import java.util.List;

import org.springframework.web.bind.annotation.DeleteMapping;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import com.example.demo_formulario.model.Area;
import com.example.demo_formulario.service.AreaService;

@RestController
@RequestMapping("/api/areas")
public class AreaController {

    private final AreaService service;

    public AreaController(AreaService service) {
        this.service = service;
    }

    @GetMapping
    public List<Area> findAll() {
        return service.findAll();
    }

    @GetMapping("/{id}")
    public Area findById(@PathVariable Integer id) {
        return service.findById(id);
    }

    @PostMapping
    public Area create(@RequestBody Area area) {
        area.setId(null);
        return service.save(area);
    }

    @PutMapping("/{id}")
    public Area update(@PathVariable Integer id, @RequestBody Area area) {
        service.findById(id);
        area.setId(id);
        return service.save(area);
    }

    @DeleteMapping("/{id}")
    public void delete(@PathVariable Integer id) {
        service.delete(id);
    }
}
