package com.example.demo_formulario.repository;

import org.springframework.data.jpa.repository.JpaRepository;

import com.example.demo_formulario.model.Rol;

public interface RolRepository extends JpaRepository<Rol, Integer> {
}
